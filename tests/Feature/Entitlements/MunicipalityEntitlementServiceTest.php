<?php

namespace Tests\Feature\Entitlements;

use App\Enums\FeatureKey;
use App\Exceptions\FeatureDependencyException;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\User;
use App\Services\Entitlements\MunicipalityEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MunicipalityEntitlementServiceTest extends TestCase
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

    public function test_absent_row_is_disabled_and_active_features_are_typed(): void
    {
        $this->assertFalse($this->service->enabledFor($this->municipality, FeatureKey::ApplicationIntake));

        MunicipalityFeatureEntitlement::factory()
            ->for($this->municipality)
            ->forFeature(FeatureKey::ApplicationIntake)
            ->enabled()
            ->create();

        $freshService = app(MunicipalityEntitlementService::class);

        $this->assertTrue($freshService->enabledFor($this->municipality, FeatureKey::ApplicationIntake));
        $this->assertSame([FeatureKey::ApplicationIntake], $freshService->activeFor($this->municipality)->all());
    }

    public function test_enable_and_disable_invalidate_request_memoization(): void
    {
        $this->assertFalse($this->service->enabledFor($this->municipality, FeatureKey::ApplicationIntake));

        $enabled = $this->service->enableFor(
            $this->municipality,
            FeatureKey::ApplicationIntake,
            $this->actor,
            'Ativação operacional inicial.',
        );

        $this->assertTrue($enabled->enabled);
        $this->assertTrue($this->service->enabledFor($this->municipality, FeatureKey::ApplicationIntake));

        $disabled = $this->service->disableFor(
            $this->municipality,
            FeatureKey::ApplicationIntake,
            $this->actor,
            'Desativação operacional validada.',
        );

        $this->assertFalse($disabled->enabled);
        $this->assertFalse($this->service->enabledFor($this->municipality, FeatureKey::ApplicationIntake));
    }

    public function test_direct_service_call_rejects_missing_dependency(): void
    {
        $this->expectException(FeatureDependencyException::class);

        $this->service->enableFor(
            $this->municipality,
            FeatureKey::ApplicationReview,
            $this->actor,
            'Tentativa sem recolha ativa.',
        );
    }

    public function test_service_rejects_blank_or_html_justification(): void
    {
        try {
            $this->service->enableFor(
                $this->municipality,
                FeatureKey::ApplicationIntake,
                $this->actor,
                'curta',
            );

            $this->fail('A justificação curta deveria ter sido rejeitada.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseMissing('municipality_feature_entitlements', [
                'municipality_id' => $this->municipality->id,
            ]);
        }

        $this->expectException(InvalidArgumentException::class);

        $this->service->enableFor(
            $this->municipality,
            FeatureKey::ApplicationIntake,
            $this->actor,
            '<b>Ativação não permitida.</b>',
        );
    }
}
