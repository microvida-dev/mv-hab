<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_access_seeder_creates_roles_and_permissions(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $this->assertDatabaseHas('roles', [
            'name' => 'administrator',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'candidate',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'applications.view',
            'module' => 'applications',
            'action' => 'view',
        ]);
    }

    public function test_user_receives_permissions_through_assigned_role(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('municipal_technician');

        $this->assertTrue($user->hasRole('municipal_technician'));
        $this->assertTrue($user->hasPermissionTo('applications', 'view'));
        $this->assertTrue($user->hasPermissionTo('documents', 'approve'));
        $this->assertFalse($user->hasPermissionTo('settings', 'delete'));
    }

    public function test_administrator_role_has_full_system_permission(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('administrator');

        $this->assertTrue($user->hasPermissionTo('settings', 'delete'));
        $this->assertTrue($user->hasPermissionTo('payments', 'reject'));
        $this->assertTrue($user->hasPermissionTo('audit_logs', 'audit'));
    }
}
