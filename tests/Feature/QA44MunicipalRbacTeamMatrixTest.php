<?php

namespace Tests\Feature;

use App\Models\MunicipalTeam;
use App\Models\Role;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QA44MunicipalRbacTeamMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipal_roles_and_teams_are_seeded_for_controlled_staging(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);

        foreach ([
            'administrator',
            'municipal_technician',
            'jury',
            'financial_manager',
            'maintenance_manager',
            'legal_manager',
            'housing_manager',
            'inspection_manager',
            'support_agent',
            'candidate',
            'auditor',
        ] as $role) {
            $this->assertTrue(Role::query()->where('name', $role)->where('is_system', true)->exists(), "Missing role {$role}");
        }

        foreach ([
            'Gabinete Técnico',
            'Gabinete Jurídico',
            'Gabinete Financeiro',
            'Gabinete de Habitação',
            'Manutenção',
            'Vistorias',
            'Atendimento',
            'Auditoria',
        ] as $team) {
            $this->assertTrue(MunicipalTeam::query()->where('name', $team)->where('status', 'active')->exists(), "Missing team {$team}");
        }
    }

    public function test_rbac_matrix_documentation_exists_and_records_review_controls(): void
    {
        $matrix = (string) file_get_contents(base_path('docs/11-operacoes/municipal-rbac-team-matrix.md'));
        $review = (string) file_get_contents(base_path('docs/11-operacoes/municipal-access-review-checklist.md'));

        $this->assertStringContainsString('administrator', $matrix);
        $this->assertStringContainsString('support_agent', $matrix);
        $this->assertStringContainsString('Auditoria', $matrix);
        $this->assertStringContainsString('revisao trimestral', $review);
        $this->assertStringContainsString('ultimo administrator ativo', $review);
        $this->assertStringContainsString('MFA', $review);
    }
}
