<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\ContestStatus;
use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\PublicVisibilityStatus;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Program;

trait CreatesAdvancedPublicPortalFixtures
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publicProgram(array $overrides = []): Program
    {
        return Program::factory()->published()->create($overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publicContest(?Program $program = null, array $overrides = []): Contest
    {
        return Contest::factory()
            ->for($program ?? $this->publicProgram())
            ->create(array_merge([
                'status' => ContestStatus::Published->value,
                'published_at' => now()->subDay(),
                'opens_at' => now()->subDay(),
                'closes_at' => now()->addMonth(),
            ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publicHousingUnit(array $overrides = []): HousingUnit
    {
        return HousingUnit::factory()->publiclyVisible()->create(array_merge([
            'public_title' => 'Habitação pública QA34 '.fake()->unique()->numerify('###'),
            'public_slug' => 'habitacao-publica-qa34-'.fake()->unique()->numerify('###'),
            'public_summary' => 'Resumo público fictício sem dados pessoais.',
            'public_description' => 'Descrição pública fictícia para validação do portal municipal.',
            'typology' => 'T2',
            'parish' => 'Alcanena',
            'locality' => 'Minde',
            'public_location_description' => 'Zona pública de Minde',
            'monthly_rent' => 325,
            'energy_rating' => 'B',
            'public_status' => HousingPublicStatus::Available->value,
            'public_location_precision' => HousingLocationPrecision::Parish->value,
            'public_latitude' => 39.4595678,
            'public_longitude' => -8.6674567,
            'public_visibility_status' => PublicVisibilityStatus::Published->value,
            'is_public' => true,
            'published_at' => now()->subHour(),
            'unpublished_at' => null,
        ], $overrides));
    }

    private function attachToContest(HousingUnit $housingUnit, Contest $contest, bool $accessible = false): ContestHousingUnit
    {
        return ContestHousingUnit::factory()
            ->for($contest)
            ->for($housingUnit)
            ->create([
                'program_id' => $contest->program_id,
                'typology' => $housingUnit->typology,
                'monthly_rent' => $housingUnit->monthly_rent,
                'accessible' => $accessible,
            ]);
    }
}
