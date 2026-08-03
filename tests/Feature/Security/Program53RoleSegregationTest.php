<?php

namespace Tests\Feature\Security;

use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleAssignmentService;
use App\Services\Access\RoleManagementService;
use App\Services\Security\MfaEnforcementService;
use Database\Seeders\SystemAccessSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Program53RoleSegregationTest extends TestCase
{
    use RefreshDatabase;

    private Municipality $municipality;

    private User $administrator;

    private Role $programRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipality = Municipality::factory()->create();
        $this->administrator = $this->userWithRole('administrator');
        $this->programRole = app(RoleManagementService::class)->applyTemplate(
            $this->administrator,
            'analista-candidaturas-exportacao',
            'Criar perfil municipal para testar a segregação do Programa 53.',
        );
    }

    public function test_active_internal_user_can_receive_template_without_entitlement_or_platform_assignment_side_effects(): void
    {
        $target = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
            'mfa_required' => false,
        ]);

        app(RoleAssignmentService::class)->assign(
            $this->administrator,
            $target,
            $this->programRole,
            'Atribuir perfil operacional validado.',
        );

        $this->assertTrue($target->refresh()->roles()->whereKey($this->programRole->id)->exists());
        $this->assertTrue(app(MfaEnforcementService::class)->requiresMfa($target));
        $this->assertFalse($target->hasPermission('reports.export_sensitive'));
        $this->assertSame(0, MunicipalityFeatureEntitlement::query()->count());
        $this->assertSame(0, PlatformOperatorAssignment::query()->count());
    }

    public function test_candidate_cannot_receive_internal_municipal_template(): void
    {
        $candidate = $this->userWithRole('candidate');

        try {
            app(RoleAssignmentService::class)->assign(
                $this->administrator,
                $candidate,
                $this->programRole,
                'Tentativa controlada de atribuição interna a candidato.',
            );
            $this->fail('A atribuição ao candidato deveria ter sido recusada.');
        } catch (AuthorizationException) {
            $this->assertFalse($candidate->refresh()->roles()->whereKey($this->programRole->id)->exists());
        }
    }

    public function test_inactive_account_cannot_receive_template(): void
    {
        $inactive = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'inactive',
        ]);

        try {
            app(RoleAssignmentService::class)->assign(
                $this->administrator,
                $inactive,
                $this->programRole,
                'Tentativa controlada de atribuição a conta inativa.',
            );
            $this->fail('A atribuição à conta inativa deveria ter sido recusada.');
        } catch (DomainException) {
            $this->assertFalse($inactive->refresh()->roles()->whereKey($this->programRole->id)->exists());
        }
    }

    public function test_auditor_and_mutable_program_template_are_incompatible_in_both_assignment_orders(): void
    {
        $auditor = $this->userWithRole('auditor');
        $regular = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);
        app(RoleAssignmentService::class)->assign(
            $this->administrator,
            $regular,
            $this->programRole,
            'Atribuir perfil mutável antes de testar auditoria.',
        );
        $auditorRole = Role::query()->where('name', 'auditor')->firstOrFail();

        foreach ([[$auditor, $this->programRole], [$regular, $auditorRole]] as [$target, $role]) {
            try {
                app(RoleAssignmentService::class)->assign(
                    $this->administrator,
                    $target,
                    $role,
                    'Tentativa controlada de acumulação incompatível.',
                );
                $this->fail('A acumulação entre auditor e perfil mutável deveria ter sido recusada.');
            } catch (AuthorizationException) {
                $this->assertFalse($target->refresh()->roles()->whereKey($role->id)->exists());
            }
        }
    }

    public function test_sensitive_export_grant_is_separate_and_revocable_without_changing_base_template(): void
    {
        $target = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);
        app(RoleAssignmentService::class)->assign(
            $this->administrator,
            $target,
            $this->programRole,
            'Atribuir perfil base do Programa 53.',
        );
        $sensitiveRole = Role::query()->create([
            'municipality_id' => $this->municipality->id,
            'name' => 'program53_sensitive_export_grant',
            'label' => 'Autorização adicional de exportação sensível',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $sensitiveRole->permissions()->sync([
            Permission::query()
                ->where('name', 'reports.export_sensitive')
                ->valueOrFail('id'),
        ]);

        app(RoleAssignmentService::class)->assign(
            $this->administrator,
            $target,
            $sensitiveRole,
            'Conceder autorização sensível separada e explícita.',
        );
        $this->assertTrue($target->refresh()->hasPermission('reports.export_sensitive'));

        app(RoleAssignmentService::class)->remove(
            $this->administrator,
            $target,
            $sensitiveRole,
            'Revogar autorização sensível sem remover o perfil base.',
        );

        $this->assertFalse($target->refresh()->hasPermission('reports.export_sensitive'));
        $this->assertTrue($target->roles()->whereKey($this->programRole->id)->exists());
        $this->assertFalse($this->programRole->permissions()->where('name', 'reports.export_sensitive')->exists());
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
