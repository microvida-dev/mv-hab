<?php

namespace Tests\Feature\UX\Concerns;

use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DocumentSubmission;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;

trait CreatesEnterpriseCaseFixtures
{
    protected function seedAccess(): void
    {
        $this->seed(SystemAccessSeeder::class);
    }

    protected function userWithRole(string $role = 'administrator'): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<string, object>
     */
    protected function enterpriseCases(): array
    {
        return [
            'contest' => Contest::factory()->open()->create(),
            'contract' => Contract::factory()->create(),
            'maintenance_request' => MaintenanceRequest::factory()->create(),
            'inspection' => PropertyInspection::factory()->create(),
            'complaint' => Complaint::factory()->create(),
            'support_ticket' => SupportTicket::factory()->create(),
            'housing_unit' => HousingUnit::factory()->create(['parish' => 'Alcanena', 'locality' => 'Centro']),
            'document_case' => DocumentSubmission::factory()->create(['storage_path' => 'documents/private/NIF-123456789.pdf']),
            'rgpd_request' => DataSubjectRequest::factory()->create(),
            'audit_case' => AuditEvent::factory()->create(['description' => 'Evento sem dados pessoais reais.']),
        ];
    }

    protected function assertEnterpriseWorkspace(string $routeName, object $case, string $expectedLabel): void
    {
        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route($routeName, $case))
            ->assertOk()
            ->assertSee('Espaço de Trabalho Enterprise')
            ->assertSee($expectedLabel)
            ->assertSee('Resumo processual')
            ->assertSee('Cronologia')
            ->assertSee('Checklist')
            ->assertSee('Painel do caso');
    }
}
