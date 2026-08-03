<?php

namespace Tests\Unit\Access;

use App\Services\Access\MunicipalRoleTemplateRegistry;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContestOperationsMunicipalRoleTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_contest_operations_template_has_the_approved_exact_boundaries(): void
    {
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('tecnico-operacoes-concurso');

        $this->assertSame('Técnico de Operações do Concurso', $template['label']);
        $this->assertSame('1.0.0', $template['version']);
        $this->assertSame([
            'applications.intake',
            'applications.review',
            'applications.export',
        ], $template['entitlement_dependencies']);
        $this->assertSame('program53_mutable', $template['segregation_class']);
        $this->assertCount(120, $template['permissions']);
        $this->assertCount(120, array_unique($template['permissions']));
        $this->assertFalse(collect($template['permissions'])->contains(
            fn (string $permission): bool => str_contains($permission, '*'),
        ));

        $this->assertContains('applications.create', $template['permissions']);
        $this->assertContains('documents.approve', $template['permissions']);
        $this->assertContains('administrative_processes.publish', $template['permissions']);
        $this->assertContains('public_lists.publish', $template['permissions']);
        $this->assertContains('hearings.issue', $template['permissions']);
        $this->assertContains('visits.availabilities.generate_slots', $template['permissions']);
        $this->assertContains('visits.complete', $template['permissions']);
        $this->assertContains('reports.export', $template['permissions']);
        $this->assertContains('reports.export_sensitive', $template['permissions']);
        $this->assertContains('reports.export_nominal', $template['permissions']);

        $this->assertNotContains('users.view', $template['permissions']);
        $this->assertNotContains('roles.assign', $template['permissions']);
        $this->assertNotContains('platform_operators.manage', $template['permissions']);
        $this->assertNotContains('scoring.run', $template['permissions']);
        $this->assertNotContains('complaints.decide', $template['permissions']);
        $this->assertNotContains('allocations.approve', $template['permissions']);
        $this->assertNotContains('lotteries.run', $template['permissions']);
        $this->assertNotContains('contracts.sign', $template['permissions']);
        $this->assertNotContains('payments.approve', $template['permissions']);
        $this->assertNotContains('finance.approve', $template['permissions']);
        $this->assertNotContains('maintenance_requests.approve', $template['permissions']);
        $this->assertNotContains('inspections.approve', $template['permissions']);
        $this->assertNotContains('privacy.approve', $template['permissions']);
        $this->assertNotContains('rgpd.retention.execute', $template['permissions']);
    }
}
