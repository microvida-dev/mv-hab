<?php

namespace Database\Factories;

use App\Models\MaintenanceSupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceSupplier>
 */
class MaintenanceSupplierFactory extends Factory
{
    protected $model = MaintenanceSupplier::class;

    public function definition(): array
    {
        return [
            'name' => 'Fornecedor Demo '.fake()->unique()->numberBetween(100, 999),
            'contact_person' => 'Contacto técnico demo',
            'email' => fake()->safeEmail(),
            'phone' => '210000000',
            'service_scope' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
