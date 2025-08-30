<?php

namespace App\Console\Commands;

use App\Models\DeviceTokensV2;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\File;

class FcmTokensCleanup extends Command
{
    const failedReg = 1;
    const failedReg_failedCheck = 2;

    protected $signature = 'app:fcm-tokens-cleanup';
    protected $description = 'Clean up invalid FCM tokens and log results';

    // Add these properties to track batch information
    private $executionTime;
    private $baseLogPath;
    private $successPath;
    private $failPath;
    private $batchCount = 0;

    public function handle()
    {
        $this->executionTime = now()->format('Y-m-d_H-i-s');
        $this->setupLogDirectories();

        /** @var $messaging \Kreait\Firebase\Messaging */
        $messaging = app('firebase.messaging');
        $tokens_table_name = app(DeviceTokensV2::class)->getTable();

        DeviceTokensV2::chunkById(1000, function (Collection $tokensChunk) use ($messaging, $tokens_table_name) {
            $this->batchCount++;
            $tokens = $tokensChunk->pluck('token', 'id')->toArray();

            $successTokens = [];
            $failedTokens = [];

            try {
                $result = $messaging->validateRegistrationTokens($tokens);
                
                // Track successful validations
                $successTokens = $result['valid'];

                $failedRegTokensArray = $result['unknown'] + $result['invalid'];

                foreach ($failedRegTokensArray as $invalidToken) {
                    $invalidLvl = self::failedReg;
                    $failureReason = 'unknown';

                    try {
                        $appInstance = $messaging->getAppInstance($invalidToken);
                        $failureReason = 'invalid';
                    } catch (\Exception $e) {
                        $invalidLvl = $invalidLvl | self::failedReg_failedCheck;
                        $failureReason = 'failed_check';
                    }

                    // Track failed token with reason
                    $failedTokens[] = [
                        'token' => $invalidToken,
                        'reason' => $failureReason,
                        'level' => $invalidLvl
                    ];

                    if ($invalidLvl == self::failedReg) {
                        DB::table($tokens_table_name)->where('token', $invalidToken)->update(['not_valid_in_topic' => $invalidLvl]);
                    } else {
                        DeviceTokensV2::where('token', $invalidToken)->delete();
                    }
                }

            } catch (MessagingException|FirebaseException $e) {
                Log::error($e->getCode());
                Log::error($e->getMessage());
                
                // Mark entire batch as failed due to Firebase error
                foreach ($tokens as $id => $token) {
                    $failedTokens[] = [
                        'token' => $token,
                        'reason' => 'firebase_error',
                        'level' => null
                    ];
                }
            }

            // Log results for this batch
            $this->logBatchResults($successTokens, $failedTokens);

        });

        $this->info("Token cleanup completed. Check logs in: {$this->baseLogPath}");
    }

    private function setupLogDirectories()
    {
        $this->baseLogPath = storage_path("logs/fcm-cleanup/{$this->executionTime}");
        $this->successPath = "{$this->baseLogPath}/success";
        $this->failPath = "{$this->baseLogPath}/fail";

        // Create directories if they don't exist
        File::makeDirectory($this->successPath, 0755, true, true);
        File::makeDirectory($this->failPath, 0755, true, true);
    }

    private function logBatchResults(array $successTokens, array $failedTokens)
    {
        $batchNumber = $this->batchCount;
        $timestamp = now()->format('Y-m-d H:i:s');

        // Log successful tokens
        if (!empty($successTokens)) {
            $successFilename = "{$this->successPath}/batch_{$batchNumber}.json";
            File::put($successFilename, json_encode([
                'batch' => $batchNumber,
                'timestamp' => $timestamp,
                'total_tokens' => count($successTokens),
                'tokens' => $successTokens
            ], JSON_PRETTY_PRINT));
        }

        // Log failed tokens
        if (!empty($failedTokens)) {
            $failFilename = "{$this->failPath}/batch_{$batchNumber}.json";
            File::put($failFilename, json_encode([
                'batch' => $batchNumber,
                'timestamp' => $timestamp,
                'total_tokens' => count($failedTokens),
                'tokens' => $failedTokens
            ], JSON_PRETTY_PRINT));
        }

        $this->info("Processed batch {$batchNumber} - Success: " . count($successTokens) . ", Failed: " . count($failedTokens));
    }
}
