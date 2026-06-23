<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\ContestStatus;
use App\Enums\HousingPublicStatus;
use App\Enums\PublicVisibilityStatus;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHousingPresentationSprint32Test extends TestCase
{
    use RefreshDatabase;

    public function test_public_housing_search_exposes_rent_status_and_applies_demo_filters(): void
    {
        $contest = $this->openContest();

        $visibleUnit = $this->publicHousingUnit([
            'public_title' => 'T2 Alcanena Sprint 32',
            'public_slug' => 't2-alcanena-sprint-32',
            'typology' => 'T2',
            'parish' => 'Alcanena',
            'monthly_rent' => 320,
            'public_status' => HousingPublicStatus::Available->value,
        ]);
        $filteredOutUnit = $this->publicHousingUnit([
            'public_title' => 'T3 Minde Sprint 32',
            'public_slug' => 't3-minde-sprint-32',
            'typology' => 'T3',
            'parish' => 'Minde',
            'monthly_rent' => 520,
            'public_status' => HousingPublicStatus::Reserved->value,
        ]);

        ContestHousingUnit::factory()->for($contest)->for($visibleUnit)->create();
        ContestHousingUnit::factory()->for($contest)->for($filteredOutUnit)->create();

        $this->get(route('public.housing-units.index'))
            ->assertOk()
            ->assertSee('Renda mínima')
            ->assertSee('Renda máxima')
            ->assertSee('Estado')
            ->assertSee('T2 Alcanena Sprint 32')
            ->assertSee('T3 Minde Sprint 32');

        $filteredResponse = $this->get(route('public.housing-units.index', [
            'parish' => 'Alcanena',
            'typology' => 'T2',
            'rent_max' => 350,
            'public_status' => HousingPublicStatus::Available->value,
        ]));

        $filteredResponse->assertOk();
        $filteredHtml = $filteredResponse->getContent();

        $this->assertStringContainsString('T2 Alcanena Sprint 32', $filteredHtml);
        $this->assertStringNotContainsString('T3 Minde Sprint 32', $filteredHtml);
    }

    public function test_public_housing_brochure_is_printable_and_keeps_private_address_hidden(): void
    {
        $contest = $this->openContest(['title' => 'Concurso Alcanena Sprint 32']);
        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'T1 Brochura Sprint 32',
            'public_slug' => 't1-brochura-sprint-32',
            'public_summary' => 'Resumo público para brochura.',
            'address' => 'Rua Privada de Teste 123',
            'public_address_visible' => false,
            'typology' => 'T1',
            'gross_area_sqm' => 58.5,
            'usable_area_sqm' => 49.2,
            'monthly_rent' => 320,
        ]);

        ContestHousingUnit::factory()->for($contest)->for($housingUnit)->create([
            'program_id' => $contest->program_id,
            'monthly_rent' => 320,
            'typology' => 'T1',
            'bedrooms' => 1,
        ]);

        $this->get(route('public.housing-units.brochure', $housingUnit->public_slug))
            ->assertOk()
            ->assertSee('Brochura informativa')
            ->assertSee('Imprimir / guardar PDF')
            ->assertSee('T1 Brochura Sprint 32')
            ->assertSee('Concurso Alcanena Sprint 32')
            ->assertSee('A morada completa só é apresentada')
            ->assertDontSee('Rua Privada de Teste 123');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publicHousingUnit(array $overrides = []): HousingUnit
    {
        return HousingUnit::factory()->publiclyVisible()->create(array_merge([
            'public_title' => 'Habitação pública Sprint 32',
            'public_slug' => 'habitacao-publica-sprint-32-'.fake()->unique()->numerify('###'),
            'public_summary' => 'Resumo público sem dados pessoais.',
            'parish' => 'Alcanena',
            'public_location_description' => 'Freguesia de Alcanena',
            'public_visibility_status' => PublicVisibilityStatus::Published->value,
            'is_public' => true,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function openContest(array $overrides = []): Contest
    {
        return Contest::factory()
            ->for(Program::factory()->published())
            ->create(array_merge([
                'status' => ContestStatus::Published->value,
                'published_at' => now()->subDay(),
                'opens_at' => now()->subDay(),
                'closes_at' => now()->addMonth(),
            ], $overrides));
    }
}
