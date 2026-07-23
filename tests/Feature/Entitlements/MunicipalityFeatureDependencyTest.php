<?php

namespace Tests\Feature\Entitlements;

use App\Enums\FeatureKey;
use App\Exceptions\FeatureDependencyException;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Entitlements\MunicipalityEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalityFeatureDependencyTest extends TestCase
{
    use RefreshDatabase;

    private MunicipalityEntitlementService $service;

    private Municipality $municipality;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MunicipalityEntitlementService::class);
        $this->municipality = Municipality::factory()->create();
        $this->actor = User::factory()->create();
    }

    public function test_review_and_export_can_be_enabled_independently_after_intake(): void
    {
        $this->enable(FeatureKey::ApplicationIntake);
        $this->enable(FeatureKey::ApplicationReview);

        $this->assertTrue($this->service->enabledFor($this->municipality, FeatureKey::ApplicationReview));
        $this->assertFalse($this->service->enabledFor($this->municipality, FeatureKey::ApplicationExport));

        $this->disable(FeatureKey::ApplicationReview);
        $this->enable(FeatureKey::ApplicationExport);

        $this->assertFalse($this->service->enabledFor($this->municipality, FeatureKey::ApplicationReview));
        $this->assertTrue($this->service->enabledFor($this->municipality, FeatureKey::ApplicationExport));
    }

    public function test_review_and_export_are_rejected_without_intake(): void
    {
        foreach ([FeatureKey::ApplicationReview, FeatureKey::ApplicationExport] as $feature) {
            try {
                $this->enable($feature);
                $this->fail($feature->value.' deveria exigir recolha ativa.');
            } catch (FeatureDependencyException) {
                $this->assertFalse($this->service->enabledFor($this->municipality, $feature));
            }
        }
    }

    public function test_intake_cannot_be_disabled_while_a_dependant_is_active_and_no_cascade_occurs(): void
    {
        foreach ([FeatureKey::ApplicationReview, FeatureKey::ApplicationExport] as $dependant) {
            $this->enable(FeatureKey::ApplicationIntake);
            $this->enable($dependant);

            try {
                $this->disable(FeatureKey::ApplicationIntake);
                $this->fail('A recolha não deveria ser desativada com uma dependência ativa.');
            } catch (FeatureDependencyException) {
                $this->assertTrue($this->service->enabledFor($this->municipality, FeatureKey::ApplicationIntake));
                $this->assertTrue($this->service->enabledFor($this->municipality, $dependant));
            }

            $this->disable($dependant);
            $this->disable(FeatureKey::ApplicationIntake);
        }
    }

    private function enable(FeatureKey $feature): void
    {
        $this->service->enableFor(
            $this->municipality,
            $feature,
            $this->actor,
            'Alteração autorizada para testes.',
        );
    }

    private function disable(FeatureKey $feature): void
    {
        $this->service->disableFor(
            $this->municipality,
            $feature,
            $this->actor,
            'Alteração autorizada para testes.',
        );
    }
}
