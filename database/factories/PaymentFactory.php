<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(PaymentStatus::values());

        return [
            'contract_id' => Contract::factory(),
            'amount' => fake()->randomFloat(2, 100, 650),
            'due_date' => fake()->dateTimeBetween('-2 months', '+2 months')->format('Y-m-d'),
            'paid_at' => $status === PaymentStatus::Paid->value
                ? fake()->dateTimeBetween('-1 month', 'now')
                : null,
            'status' => $status,
            'reference' => 'PAG-'.fake()->unique()->numerify('######'),
        ];
    }
}
