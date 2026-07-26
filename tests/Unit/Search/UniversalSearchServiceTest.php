<?php

namespace Tests\Unit\Search;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\User;
use App\Services\Search\UniversalSearchService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class UniversalSearchServiceTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
    }

    public function test_service_returns_grouped_authorized_results(): void
    {
        $administrator = $this->userWithRole('administrator');
        Application::factory()->submitted()->create([
            'program_id' => Program::factory()->create(['municipality_id' => $this->municipality->id]),
            'application_number' => 'CAND-2026-SERVICE-001',
        ]);

        $result = $this->app->make(UniversalSearchService::class)
            ->search($administrator, 'SERVICE');

        $labels = collect($result['groups'])
            ->flatMap(fn (array $group): array => $group['results'])
            ->pluck('label')
            ->all();

        $this->assertContains('Candidatura CAND-2026-SERVICE-001', $labels);
    }

    public function test_short_term_returns_only_sources_that_accept_short_queries(): void
    {
        $administrator = $this->userWithRole('administrator');
        Application::factory()->submitted()->create([
            'program_id' => Program::factory()->create(['municipality_id' => $this->municipality->id]),
            'application_number' => 'CAND-2026-SHORT-001',
        ]);

        $result = $this->app->make(UniversalSearchService::class)
            ->search($administrator, 'C');

        $labels = collect($result['groups'])
            ->flatMap(fn (array $group): array => $group['results'])
            ->pluck('label')
            ->all();

        $this->assertNotContains('Candidatura CAND-2026-SHORT-001', $labels);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
