<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_user_can_get_all_orders()
    {
        // Create some orders for the user
        Order::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_order()
    {
        $orderData = [
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
                ['product_id' => 2, 'quantity' => 1],
            ],
            'total_amount' => 299.99,
            'shipping_address' => $this->faker->address,
            'status' => 'pending'
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'items',
                    'total_amount',
                    'shipping_address',
                    'status',
                    'user_id'
                ]);
    }

    public function test_user_can_get_specific_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $order->id,
                    'user_id' => $this->user->id
                ]);
    }

    public function test_user_cannot_get_other_users_order()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_update_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'shipping_address' => $this->faker->address,
            'status' => 'processing'
        ];

        $response = $this->putJson("/api/orders/{$order->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'shipping_address' => $updateData['shipping_address'],
                    'status' => $updateData['status']
                ]);
    }

    public function test_user_can_delete_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_validation_fails_for_invalid_order_data()
    {
        $invalidData = [
            'items' => 'not-an-array', // should be array
            'total_amount' => 'not-a-number', // should be numeric
            'status' => 'invalid-status' // should be one of the valid statuses
        ];

        $response = $this->postJson('/api/orders', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['items', 'total_amount', 'status']);
    }
} 