<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Database\Seeders\DashboardDefinitionSeeder;
use Database\Seeders\DashboardWidgetSeeder;
use Database\Seeders\IndicatorDefinitionSeeder;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalReportsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SystemAccessSeeder::class,
            IndicatorDefinitionSeeder::class,
            DashboardDefinitionSeeder::class,
            DashboardWidgetSeeder::class,
            ReportDefinitionSeeder::class,
        ]);
    }

    public function test_candidate_and_tenant_profiles_do_not_access_municipal_reports(): void
    {
        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.reports.operational'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.reports.exports.index'))
            ->assertForbidden();
    }

    public function test_auditor_has_read_only_access_to_report_audit_logs(): void
    {
        $auditor = $this->userWithRole('auditor');

        $this->actingAs($auditor)
            ->get(route('backoffice.reports.access-logs.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->post(route('backoffice.reports.definitions.store'), [])
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
