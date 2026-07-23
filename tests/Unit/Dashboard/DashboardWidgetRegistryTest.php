<?php

namespace Tests\Unit\Dashboard;

use App\Enums\FeatureKey;
use App\Models\DocumentSubmission;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Dashboard\DashboardWidgetRegistry;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class DashboardWidgetRegistryTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipality = $this->municipalityWithFeatures(FeatureKey::cases());
    }

    public function test_auditor_receives_read_only_audit_widget(): void
    {
        $auditor = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);
        $auditor->assignRole('auditor');

        $widgets = app(DashboardWidgetRegistry::class)->forUser($auditor);

        $this->assertContains('audit_readonly', array_column($widgets, 'key'));
        $this->assertSame('Auditoria em leitura', $widgets[0]['title']);
        $this->assertArrayHasKey('icon', $widgets[0]);
        $this->assertArrayHasKey('value', $widgets[0]);
        $this->assertArrayHasKey('tone', $widgets[0]);
        $this->assertArrayHasKey('priority', $widgets[0]);
        $this->assertArrayHasKey('cta', $widgets[0]);
    }

    public function test_municipal_technician_receives_intelligent_document_review_widget(): void
    {
        $technician = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);
        $technician->assignRole('municipal_technician');
        $candidate = User::factory()->create(['municipality_id' => $this->municipality->id]);

        DocumentSubmission::factory()->count(2)->create([
            'user_id' => $candidate->id,
            'status' => 'submitted',
        ]);

        $widgets = app(DashboardWidgetRegistry::class)->forUser($technician);
        $widget = collect($widgets)->firstWhere('key', 'technical_review');

        $this->assertNotNull($widget);
        $this->assertSame('Revisão técnica', $widget['title']);
        $this->assertSame('document', $widget['icon']);
        $this->assertSame(2, $widget['value']);
        $this->assertSame('Documentos pendentes', $widget['meta']);
        $this->assertSame('warning', $widget['tone']);
        $this->assertSame('high', $widget['priority']);
        $this->assertSame('Abrir revisão', $widget['cta']);
    }
}
