<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use Database\Seeders\Testing\IntegratedWorkflowTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BasicLoadSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegratedWorkflowTestSeeder::class);
    }

    public function test_public_candidate_and_backoffice_pages_stay_within_basic_query_budgets(): void
    {
        $candidate = User::query()->where('email', 's19-eligible@example.test')->firstOrFail();
        $auditor = User::query()->where('email', 's19-auditor@example.test')->firstOrFail();

        $this->assertQueryBudget(route('public.contests.index'), 80);
        $this->assertQueryBudget(route('public.programs.index'), 80);

        $this->actingAs($candidate);
        $this->assertQueryBudget(route('candidate.dashboard'), 160);
        $this->assertQueryBudget(route('candidate.documents.checklist'), 180);

        $this->actingAs($auditor);
        $this->assertQueryBudget(route('backoffice.reports.index'), 220);
    }

    private function assertQueryBudget(string $url, int $maxQueries): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get($url)->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $maxQueries,
            count($queries),
            "A rota [$url] executou ".count($queries)." queries, acima do orçamento de $maxQueries.",
        );
    }
}
