<?php

namespace Tests\Unit\Search;

use App\Models\User;
use App\Services\Search\Sources\CommandSearchSource;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandSearchSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_command_source_returns_authorized_commands_only(): void
    {
        $administrator = $this->userWithRole('administrator');
        $supportAgent = $this->userWithRole('support_agent');
        $source = $this->app->make(CommandSearchSource::class);

        $adminLabels = array_column($source->search($administrator, 'auditoria', 10), 'label');
        $supportLabels = array_column($source->search($supportAgent, 'auditoria', 10), 'label');

        $this->assertContains('Abrir auditoria', $adminLabels);
        $this->assertNotContains('Abrir auditoria', $supportLabels);
    }

    public function test_command_source_does_not_include_destructive_commands(): void
    {
        $administrator = $this->userWithRole('administrator');
        $source = $this->app->make(CommandSearchSource::class);

        $labels = implode(' ', array_column($source->search($administrator, '', 50), 'label'));

        $this->assertStringNotContainsString('Eliminar', $labels);
        $this->assertStringNotContainsString('Apagar', $labels);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
