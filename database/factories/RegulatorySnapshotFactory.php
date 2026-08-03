<?php

namespace Database\Factories;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryContext;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Program;
use App\Models\RegulatorySnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegulatorySnapshot>
 */
class RegulatorySnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'municipality_id' => null,
            'regulatory_profile_id' => AffordableRentRegulatoryProfile::factory(),
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
            'context' => RegulatoryContext::ProgramPublication,
            'source_type' => (new Program)->getMorphClass(),
            'source_id' => fake()->unique()->numberBetween(100000, 999999),
            'reference_date' => '2026-08-31 12:00:00',
            'profile_code' => 'PAA-TEST',
            'profile_version' => '1.0',
            'legal_basis' => 'Fonte oficial de teste sem valor jurídico.',
            'rule_sets' => [],
            'limits' => [],
            'parameters' => [],
            'municipal_overlay' => null,
            'origin' => 'test',
            'checksum' => hash('sha256', fake()->uuid()),
            'created_by' => null,
            'locked_at' => now(),
        ];
    }
}
