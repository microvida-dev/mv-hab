<?php

namespace Database\Factories;

use App\Models\RentLimitTableManifest;
use App\Models\RentLimitTableRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentLimitTableRow>
 */
class RentLimitTableRowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'manifest_id' => RentLimitTableManifest::factory(),
            'municipality_code' => 'TESTE',
            'typology' => 'T1',
            'minimum_rent' => '100.00',
            'maximum_rent' => '500.00',
            'source_row_reference' => 'Linha de teste',
        ];
    }
}
