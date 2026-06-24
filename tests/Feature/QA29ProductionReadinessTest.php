<?php

namespace Tests\Feature;

use App\Enums\ContestHousingUnitStatus;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\HousingUnitPublicDocument;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QA29ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa29_public_smoke_routes_load_without_exposing_private_housing_data(): void
    {
        Storage::fake('public');

        $program = Program::factory()->published()->create([
            'name' => 'Programa Público QA29',
            'slug' => 'programa-publico-qa29',
        ]);

        $contest = Contest::factory()
            ->for($program)
            ->open()
            ->create([
                'title' => 'Concurso Público QA29',
                'slug' => 'concurso-publico-qa29',
            ]);

        $housingUnit = HousingUnit::factory()->publiclyVisible()->create([
            'public_title' => 'Habitação Pública QA29',
            'public_slug' => 'habitacao-publica-qa29',
            'address' => 'Rua Interna QA29 123',
            'public_address_visible' => false,
            'parish' => 'Alcanena',
            'public_latitude' => 39.4595000,
            'public_longitude' => -8.6674000,
        ]);

        ContestHousingUnit::factory()
            ->for($program)
            ->for($contest)
            ->for($housingUnit)
            ->create([
                'status' => ContestHousingUnitStatus::Available->value,
            ]);

        $publicDocument = HousingUnitPublicDocument::factory()
            ->for($housingUnit)
            ->create([
                'title' => 'Ficha pública QA29',
                'path' => 'public-housing/documents/qa29-public.pdf',
            ]);

        Storage::disk('public')->put($publicDocument->path, 'PDF publico QA29');

        $privateDocument = HousingUnitPublicDocument::factory()
            ->private()
            ->for($housingUnit)
            ->create([
                'title' => 'Documento privado QA29',
                'path' => 'public-housing/documents/qa29-private.pdf',
            ]);

        Storage::disk('public')->put($privateDocument->path, 'PDF privado QA29');

        $this->get(route('public.portal'))->assertOk()->assertSee('Concurso Público QA29');
        $this->get(route('public.programs.index'))->assertOk()->assertSee('Programa Público QA29');
        $this->get(route('public.contests.index'))->assertOk()->assertSee('Concurso Público QA29');
        $this->get(route('public.contests.show', $contest->slug))->assertOk()->assertSee('Habitação Pública QA29');
        $this->get(route('public.housing-offer.index'))->assertOk()->assertSee('Habitação Pública QA29');
        $this->get(route('public.housing-units.index'))->assertOk()->assertSee('Habitação Pública QA29');

        $this->get(route('public.housing-units.show', $housingUnit->public_slug))
            ->assertOk()
            ->assertSee('Ficha pública QA29')
            ->assertDontSee('Rua Interna QA29 123')
            ->assertDontSee('public-housing/documents/qa29-public.pdf')
            ->assertDontSee('public-housing/documents/qa29-private.pdf')
            ->assertDontSee('Documento privado QA29');

        $this->getJson(route('public.housing-map.index'))
            ->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('markers.0.title', 'Habitação Pública QA29')
            ->assertJsonMissing(['address' => 'Rua Interna QA29 123']);

        $this->get(route('public.housing-documents.download', $publicDocument))->assertOk();
        $this->get(route('public.housing-documents.download', $privateDocument))->assertNotFound();
    }

    public function test_qa29_private_application_areas_redirect_guests_to_login(): void
    {
        $privateRoutes = [
            route('dashboard'),
            route('candidate.dashboard'),
            route('candidate.documents.checklist'),
            route('tenant.dashboard'),
            route('backoffice.reports.index'),
        ];

        foreach ($privateRoutes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_qa29_private_storage_is_not_publicly_exposed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('qa29/private-document.pdf', 'conteudo privado QA29');

        $response = $this->get('/storage/qa29/private-document.pdf');
        $localRoot = config('filesystems.disks.local.root');
        $publicRoot = config('filesystems.disks.public.root');
        $links = config('filesystems.links');

        $this->assertIsString($localRoot);
        $this->assertIsString($publicRoot);
        $this->assertIsArray($links);
        $this->assertContains($response->status(), [403, 404]);
        $this->assertSame(app()->storagePath().'/app/private', $localRoot);
        $this->assertSame(app()->storagePath().'/app/public', $publicRoot);
        $this->assertNotContains($localRoot, array_values($links));
    }

    public function test_qa29_critical_environment_configuration_does_not_assume_debug_in_production(): void
    {
        $localDisk = config('filesystems.disks.local');
        $publicDisk = config('filesystems.disks.public');

        $this->assertIsArray($localDisk);
        $this->assertIsArray($publicDisk);
        $this->assertFalse(
            config('app.env') === 'production' && config('app.debug') === true,
            'Ambiente production não pode correr com debug ativo.',
        );

        $this->assertSame('local', config('filesystems.default'));
        $this->assertArrayNotHasKey('visibility', $localDisk);
        $this->assertSame('public', $publicDisk['visibility'] ?? null);
    }
}
