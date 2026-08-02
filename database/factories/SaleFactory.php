<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'receipt_number' => null,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'service_area' => ServiceArea::Standard,
            'sale_status' => SaleStatus::Completed,
            'total_amount' => 0,
            'cash_register_closing_id' => null,
        ];
    }
}
