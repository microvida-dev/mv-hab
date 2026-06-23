<?php

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'citizen_id' => Citizen::factory(),
            'housing_application_id' => null,
            'contract_id' => null,
            'name' => fake()->unique()->lexify('documento_????').'.pdf',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(50_000, 500_000),
        ];
    }
}
