<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveBackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_sensitive_exports_and_documents_are_limited_to_authorized_profiles(): void
    {
        $administrator = $this->userWithRole('administrator');
        $support = $this->userWithRole('support_agent');
        $maintenance = $this->userWithRole('maintenance_manager');
        $financial = $this->userWithRole('financial_manager');

        $this->assertTrue($administrator->hasPermission('exports.sensitive.download'));
        $this->assertFalse($support->hasPermission('exports.sensitive.download'));
        $this->assertFalse($support->hasPermission('documents.view'));
        $this->assertFalse($maintenance->hasPermission('income_records.view'));
        $this->assertFalse($financial->hasPermission('documents.view'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
