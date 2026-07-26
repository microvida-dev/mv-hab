<?php

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Document;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'municipality_id' => Municipality::factory(),
            'citizen_id' => fn (array $attributes) => Citizen::factory()->create([
                'municipality_id' => $attributes['municipality_id'],
            ]),
            'housing_application_id' => null,
            'contract_id' => null,
            'name' => fake()->unique()->lexify('documento_????').'.pdf',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(50_000, 500_000),
        ];
    }
}
