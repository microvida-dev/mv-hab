<?php

namespace Database\Factories;

use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Models\AdhesionRegistration;
use App\Models\CurrentHousingSituation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrentHousingSituation>
 */
class CurrentHousingSituationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'adhesion_registration_id' => AdhesionRegistration::factory(),
            'housing_status' => HousingStatus::Rented->value,
            'current_address' => 'Morada habitacional de teste',
            'current_postal_code' => '0000-000',
            'current_city' => 'Localidade de Teste',
            'current_parish' => 'Freguesia de Teste',
            'current_municipality' => 'Município de Teste',
            'resides_in_municipality' => true,
            'residence_years_in_municipality' => 5,
            'works_in_municipality' => false,
            'current_housing_typology' => 'T2',
            'current_housing_rooms' => 2,
            'current_housing_condition' => HousingCondition::Adequate->value,
            'current_monthly_rent' => 450,
            'current_housing_expense' => 75,
            'is_overcrowded' => false,
            'is_at_risk_of_eviction' => false,
            'is_homeless' => false,
            'is_temporary_accommodation' => false,
            'is_domestic_violence_victim' => false,
            'has_accessibility_needs' => false,
            'has_high_rent_burden' => false,
            'request_reason' => 'Motivo fictício para teste funcional.',
        ];
    }
}
