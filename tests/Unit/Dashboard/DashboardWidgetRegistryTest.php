<?php

namespace Tests\Unit\Dashboard;

use App\Models\User;
use App\Services\Dashboard\DashboardWidgetRegistry;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_auditor_receives_read_only_audit_widget(): void
    {
        $auditor = User::factory()->create(['status' => 'active']);
        $auditor->assignRole('auditor');

        $widgets = app(DashboardWidgetRegistry::class)->forUser($auditor);

        $this->assertContains('audit_readonly', array_column($widgets, 'key'));
        $this->assertSame('Auditoria em leitura', $widgets[0]['title']);
    }
}
