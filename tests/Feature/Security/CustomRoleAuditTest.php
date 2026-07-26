<?php

namespace Tests\Feature\Security;

use App\Models\AccessChangeEvent;
use App\Models\AuditEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomRoleAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_custom_role_lifecycle_records_coherent_minimized_audit_events(): void
    {
        $administrator = $this->administrator();
        $applicationView = Permission::query()->where('name', 'applications.view')->firstOrFail();
        $documentView = Permission::query()->where('name', 'documents.view')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), [
                'label' => 'Perfil auditável',
                'description' => 'Perfil dedicado ao teste do ciclo de auditoria.',
                'permissions' => [$applicationView->id],
                'justification' => 'Criar perfil para rastreabilidade integral.',
            ])
            ->assertRedirect();

        $role = Role::query()->where('label', 'Perfil auditável')->firstOrFail();
        $created = $this->event('role_created');
        $this->assertSame($administrator->id, $created->actor_id);
        $this->assertSame('Criar perfil para rastreabilidade integral.', $created->justification);
        $this->assertSame(['applications.view'], $created->new_values['permissions_added']);
        $this->assertSame(0, $created->new_values['affected_user_count']);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.roles.update', $role), [
                'label' => 'Perfil auditado',
                'description' => 'Descrição administrativa revista.',
                'justification' => 'Clarificar designação e descrição do perfil.',
            ])
            ->assertRedirect();

        $detailsUpdated = $this->event('role_updated');
        $this->assertSame('Perfil auditável', $detailsUpdated->old_values['role']['label']);
        $this->assertSame('Perfil auditado', $detailsUpdated->new_values['role']['label']);
        $this->assertSame('Clarificar designação e descrição do perfil.', $detailsUpdated->justification);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.roles.permissions.update', $role), [
                'permissions' => [$documentView->id],
                'justification' => 'Trocar consulta de candidatura por consulta documental.',
            ])
            ->assertRedirect();

        $permissionsUpdated = $this->event('role_updated');
        $this->assertSame(['documents.view'], $permissionsUpdated->new_values['permissions_added']);
        $this->assertSame(['applications.view'], $permissionsUpdated->new_values['permissions_removed']);
        $this->assertSame('Trocar consulta de candidatura por consulta documental.', $permissionsUpdated->justification);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.duplicate', $role), [
                'label' => 'Perfil auditado duplicado',
                'description' => 'Cópia sem utilizadores.',
                'justification' => 'Criar variante municipal independente.',
            ])
            ->assertRedirect();

        $copy = Role::query()->where('label', 'Perfil auditado duplicado')->firstOrFail();
        $duplicated = $this->event('role_duplicated');
        $this->assertSame($role->id, $duplicated->new_values['source_role_id']);
        $this->assertSame(['documents.view'], $duplicated->new_values['permissions_added']);
        $this->assertSame(0, $duplicated->new_values['affected_user_count']);

        $affectedUser = User::factory()->create(['status' => 'active']);
        $affectedUser->roles()->attach($copy);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.deactivate', $copy), [
                'justification' => 'Suspender perfil durante revisão.',
            ])
            ->assertRedirect();

        $deactivated = $this->event('role_deactivated');
        $this->assertSame(1, $deactivated->new_values['affected_user_count']);
        $this->assertFalse($deactivated->new_values['role']['is_active']);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.activate', $copy), [
                'justification' => 'Reativar perfil após revisão.',
            ])
            ->assertRedirect();

        $activated = $this->event('role_activated');
        $this->assertSame(1, $activated->new_values['affected_user_count']);
        $this->assertTrue($activated->new_values['role']['is_active']);

        $affectedUser->roles()->detach($copy);
        $copyId = (int) $copy->id;
        $copyName = $copy->name;

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.roles.destroy', $copy), [
                'justification' => 'Eliminar variante sem associações.',
            ])
            ->assertRedirect(route('backoffice.roles.index'));

        $deleted = $this->event('role_deleted');
        $this->assertNull($deleted->role_id);
        $this->assertSame($copyId, $deleted->old_values['role']['id']);
        $this->assertSame($copyName, $deleted->old_values['role']['name']);
        $this->assertSame(['documents.view'], $deleted->old_values['permissions']);
        $this->assertSame('Eliminar variante sem associações.', $deleted->justification);

        $audit = AuditEvent::query()->where('event_code', 'role_deleted')->latest('id')->firstOrFail();
        $this->assertSame($copyName, $audit->metadata['role_name']);
        $this->assertArrayNotHasKey('password', $audit->metadata);
        $this->assertArrayNotHasKey('token', $audit->metadata);
    }

    public function test_blocked_system_role_mutation_does_not_change_data_or_emit_success_event(): void
    {
        $administrator = $this->administrator();
        $systemRole = Role::query()->where('name', 'municipal_technician')->firstOrFail();
        $label = $systemRole->label;
        $eventsBefore = AccessChangeEvent::query()->count();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.roles.update', $systemRole), [
                'label' => 'Alteração indevida',
                'justification' => 'Tentativa de alteração de role protegida.',
            ])
            ->assertForbidden();

        $this->assertSame($label, $systemRole->refresh()->label);
        $this->assertSame($eventsBefore, AccessChangeEvent::query()->count());
    }

    private function event(string $code): AccessChangeEvent
    {
        return AccessChangeEvent::query()
            ->where('event_code', $code)
            ->latest('id')
            ->firstOrFail();
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');

        return $user;
    }
}
