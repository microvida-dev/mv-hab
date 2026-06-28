<?php

namespace Tests\Unit\Cases;

use App\Models\Contest;
use App\Models\User;
use App\Services\Cases\CaseAuthorizationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseAuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_cannot_view_enterprise_backoffice_case(): void
    {
        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');
        $contest = Contest::factory()->open()->create();

        $this->assertFalse(app(CaseAuthorizationService::class)->canViewEnterpriseCase($candidate, 'contest', $contest));
    }
}
