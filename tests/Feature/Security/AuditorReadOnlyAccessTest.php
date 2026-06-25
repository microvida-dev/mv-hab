<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditorReadOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_has_view_and_audit_without_mutation_permissions(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $auditor = User::factory()->create(['status' => 'active']);
        $auditor->assignRole('auditor');

        foreach (['applications.view', 'documents.view', 'audit_logs.view', 'work_tasks.audit', 'exports.sensitive.audit'] as $permission) {
            $this->assertTrue($auditor->hasPermission($permission), "Auditor should have {$permission}");
        }

        foreach (['applications.update', 'documents.approve', 'users.update', 'roles.assign', 'work_tasks.complete', 'exports.sensitive.download'] as $permission) {
            $this->assertFalse($auditor->hasPermission($permission), "Auditor should not have {$permission}");
        }
    }
}
