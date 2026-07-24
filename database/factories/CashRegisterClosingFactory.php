<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CashRegisterClosing>
 */
class CashRegisterClosingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalCash = fake()->numberBetween(5000, 50000);

        return [
            'closing_date' => fake()->unique()->date(),
            'closed_by' => User::factory(),
            'total_cash' => $totalCash,
            'counted_cash' => $totalCash,
            'variance' => 0,
            'total_orange_money' => fake()->numberBetween(0, 20000),
            'total_mtn_momo' => fake()->numberBetween(0, 20000),
            'total_other' => 0,
            'total_amount' => $totalCash,
            'total_orders_count' => fake()->numberBetween(1, 30),
        ];
    }
}
