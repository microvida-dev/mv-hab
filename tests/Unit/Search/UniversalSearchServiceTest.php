<?php

namespace Tests\Unit\Search;

use App\Models\Application;
use App\Models\User;
use App\Services\Search\UniversalSearchService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversalSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_service_returns_grouped_authorized_results(): void
    {
        $administrator = $this->userWithRole('administrator');
        Application::factory()->submitted()->create([
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
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
