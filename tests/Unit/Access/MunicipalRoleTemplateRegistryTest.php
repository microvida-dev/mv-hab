<?php

namespace Tests\Unit\Access;

use App\Models\Permission;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Support\CanonicalJsonHasher;
use Database\Seeders\SystemAccessSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalRoleTemplateRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_program_53_template_has_an_exact_deterministic_matrix(): void
    {
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');

        $this->assertSame('Analista de candidaturas e exportação', $template['label']);
        $this->assertSame('1.0.0', $template['version']);
        $this->assertSame([
            'dashboard.view',
            'applications.view',
            'applications.update',
            'applications.audit',
            'applications.export',
            'documents.view',
            'documents.update',
            'documents.replace',
            'documents.download',
            'documents.analyze',
            'documents.review_ai',
            'documents.approve',
            'documents.reject',
            'documents.audit',
            'eligibility.view',
            'eligibility.run',
            'administrative_processes.view',
            'administrative_processes.create',
            'administrative_processes.update',
            'administrative_processes.assign',
            'administrative_processes.decide',
            'administrative_processes.complete',
            'administrative_processes.cancel',
            'administrative_processes.issue',
            'administrative_processes.mark_overdue',
            'administrative_processes.publish',
            'administrative_processes.audit',
            'administrative_decisions.view',
            'administrative_decisions.create',
            'work_tasks.view',
            'work_tasks.claim',
            'work_tasks.update_status',
            'work_tasks.complete',
            'reports.view',
            'reports.export',
            'reports.audit',
        ], $template['permissions']);
        $this->assertSame([
            'applications.review',
            'applications.export',
        ], $template['entitlement_dependencies']);
        $this->assertContains('reports.export_sensitive', $template['excluded_permissions']);
        $this->assertNotContains('reports.export_sensitive', $template['permissions']);
        $this->assertFalse(collect($template['permissions'])->contains(
            fn (string $permission): bool => str_contains($permission, '*'),
        ));
        $this->assertCount(count($template['permissions']), array_unique($template['permissions']));
    }

    public function test_template_fingerprint_uses_version_and_sorted_permissions(): void
    {
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');
        $permissions = $template['permissions'];
        sort($permissions, SORT_STRING);

        $expected = app(CanonicalJsonHasher::class)->hash([
            'version' => $template['version'],
            'permissions' => $permissions,
        ]);

        $this->assertSame($expected, $template['fingerprint']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $template['fingerprint']);
    }

    public function test_existing_template_permission_snapshots_are_preserved(): void
    {
        $registry = app(MunicipalRoleTemplateRegistry::class);

        $this->assertSame([
            'dashboard.view',
            'applications.view',
            'applications.create',
            'applications.update',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.replace',
            'documents.download',
            'administrative_processes.view',
            'administrative_processes.create',
        ], $registry->resolve('operador-recolha')['permissions']);

        $this->assertSame([
            'dashboard.view',
            'applications.view',
            'applications.update',
            'applications.audit',
            'documents.view',
            'documents.update',
            'documents.replace',
            'documents.download',
            'documents.analyze',
            'documents.review_ai',
            'documents.approve',
            'documents.reject',
            'documents.audit',
            'eligibility.view',
            'eligibility.run',
            'administrative_processes.view',
            'administrative_processes.update',
            'administrative_processes.assign',
            'administrative_processes.decide',
            'administrative_processes.complete',
            'administrative_processes.cancel',
            'administrative_processes.issue',
            'administrative_processes.mark_overdue',
            'administrative_processes.audit',
            'administrative_decisions.view',
            'administrative_decisions.create',
            'work_tasks.view',
            'work_tasks.claim',
            'work_tasks.update_status',
            'work_tasks.complete',
        ], $registry->resolve('analista-candidaturas')['permissions']);

        $this->assertSame([
            'dashboard.view',
            'applications.view',
            'applications.export',
            'reports.view',
            'reports.export',
            'reports.audit',
        ], $registry->resolve('exportador-candidaturas')['permissions']);
    }

    public function test_resolution_fails_closed_when_a_permission_is_missing(): void
    {
        Permission::query()
            ->where('name', 'administrative_processes.publish')
            ->delete();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('administrative_processes.publish');

        app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');
    }
}
