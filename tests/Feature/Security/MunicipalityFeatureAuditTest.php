<?php

namespace Tests\Feature\Security;

use App\Enums\FeatureKey;
use App\Exceptions\FeatureDependencyException;
use App\Models\AuditEvent;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Entitlements\MunicipalityEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalityFeatureAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_and_disable_events_preserve_the_required_metadata(): void
    {
        $municipality = Municipality::factory()->create();
        $actor = User::factory()->create();
        $service = app(MunicipalityEntitlementService::class);

        $service->enableFor(
            $municipality,
            FeatureKey::ApplicationIntake,
            $actor,
            'Ativação aprovada para exploração municipal.',
        );
        $service->disableFor(
            $municipality,
            FeatureKey::ApplicationIntake,
            $actor,
            'Desativação aprovada após revisão municipal.',
        );

        $enabled = AuditEvent::query()->where('event_code', 'municipality_feature_enabled')->sole();
        $disabled = AuditEvent::query()->where('event_code', 'municipality_feature_disabled')->sole();

        $this->assertSame($actor->id, $enabled->user_id);
        $this->assertSame($municipality->id, $enabled->auditable_id);
        $this->assertSame(FeatureKey::ApplicationIntake->value, data_get($enabled->metadata, 'feature_key'));
        $this->assertFalse(data_get($enabled->metadata, 'before'));
        $this->assertTrue(data_get($enabled->metadata, 'after'));
        $this->assertSame([], data_get($enabled->metadata, 'dependencies'));
        $this->assertSame('Ativação aprovada para exploração municipal.', data_get($enabled->metadata, 'justification'));
        $this->assertTrue(data_get($disabled->metadata, 'before'));
        $this->assertFalse(data_get($disabled->metadata, 'after'));
    }

    public function test_rejected_dependency_does_not_create_a_success_event(): void
    {
        $municipality = Municipality::factory()->create();
        $actor = User::factory()->create();

        try {
            app(MunicipalityEntitlementService::class)->enableFor(
                $municipality,
                FeatureKey::ApplicationReview,
                $actor,
                'Tentativa sem a dependência obrigatória.',
            );
            $this->fail('A dependência deveria ter sido rejeitada.');
        } catch (FeatureDependencyException) {
            $this->assertDatabaseMissing('audit_events', [
                'event_code' => 'municipality_feature_enabled',
                'auditable_id' => $municipality->id,
            ]);
        }
    }
}
