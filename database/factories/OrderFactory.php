<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'items' => [
                [
                    'product_id' => fake()->numberBetween(1, 100),
                    'quantity' => fake()->numberBetween(1, 5)
                ]
            ],
            'total_amount' => fake()->randomFloat(2, 10, 1000),
            'shipping_address' => fake()->address(),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled'])
        ];
    }
} 