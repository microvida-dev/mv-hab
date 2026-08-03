<?php

namespace Database\Factories;

use App\Enums\RentLimitConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentLimitTableManifest;
use App\Models\RentRuleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentLimitTableManifest>
 */
class RentLimitTableManifestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'regulatory_profile_id' => AffordableRentRegulatoryProfile::factory(),
            'rent_rule_set_id' => RentRuleSet::factory(),
            'source_document' => 'Documento regulamentar fictício para testes automatizados.',
            'source_reference' => 'TESTE-SEM-VALOR-JURIDICO',
            'source_version' => 'test-fixture-1',
            'effective_from' => '2026-01-01',
            'effective_until' => '2026-12-31',
            'checksum' => null,
            'row_count' => 0,
            'municipality_coverage' => ['TESTE'],
            'typology_coverage' => ['T1'],
            'validation_status' => RentLimitConfigurationStatus::Configured,
            'demo_only' => true,
            'validated_at' => now(),
            'validated_by' => null,
        ];
    }
}
