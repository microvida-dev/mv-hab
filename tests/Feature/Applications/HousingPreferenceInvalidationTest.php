<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationStatus;
use App\Enums\FeatureKey;
use App\Enums\HousingCompatibilityStatus;
use App\Events\HousingPreferenceInputsChanged;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\User;
use App\Services\Allocation\HousingPreferenceService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class HousingPreferenceInvalidationTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_domain_event_is_idempotent_and_never_invalidates_submitted_preferences(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        app(HousingPreferenceService::class)->replace(
            $context['application'],
            [[
                'contest_housing_unit_id' => $unit->id,
                'preference_order' => 1,
            ]],
            $context['candidate'],
        );

        HousingPreferenceInputsChanged::dispatch(
            $context['household'],
            'Alteração repetida em teste.',
            HousingPreferenceInputsChanged::INCOME,
        );
        HousingPreferenceInputsChanged::dispatch(
            $context['household'],
            'Alteração repetida em teste.',
            HousingPreferenceInputsChanged::INCOME,
        );

        $preference = $context['application']->housingPreferences()
            ->firstOrFail();
        $this->assertSame(
            HousingCompatibilityStatus::RequiresRevalidation,
            $preference->compatibility_status,
        );
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('auditable_type', $context['application']->getMorphClass())
                ->where('auditable_id', $context['application']->id)
                ->where('action', 'housing_preferences_invalidated')
                ->count(),
        );

        $preference->forceFill([
            'compatibility_status' => HousingCompatibilityStatus::Compatible,
            'invalidated_at' => null,
            'invalidation_reason' => null,
            'submitted_at' => now(),
            'locked_at' => now(),
        ])->save();
        $context['application']->forceFill([
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
            'locked_at' => now(),
        ])->save();

        HousingPreferenceInputsChanged::dispatch(
            $context['household'],
            'Alteração posterior à submissão.',
            HousingPreferenceInputsChanged::ANNUAL_UPDATE,
        );

        $this->assertSame(
            HousingCompatibilityStatus::Compatible,
            $preference->fresh()->compatibility_status,
        );
        $this->assertNull($preference->fresh()->invalidated_at);
    }

    public function test_backoffice_household_writer_invalidates_draft_preferences(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        app(HousingPreferenceService::class)->replace(
            $context['application'],
            [[
                'contest_housing_unit_id' => $unit->id,
                'preference_order' => 1,
            ]],
            $context['candidate'],
        );
        $citizen = Citizen::factory()->create([
            'municipality_id' => $context['municipality']->id,
        ]);
        $context['household']->forceFill([
            'citizen_id' => $citizen->id,
        ])->save();
        $admin = User::factory()->create([
            'municipality_id' => $context['municipality']->id,
        ]);
        $admin->assignRole('administrator');
        $this->enableMunicipalityFeatures(
            $context['municipality'],
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        );

        $this->actingAs($admin)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('households.update', $context['household']), [
                'citizen_id' => $citizen->id,
                'name' => 'Agregado atualizado no backoffice',
                'monthly_income' => '2000.00',
                'members_count' => 1,
                'notes' => null,
            ])
            ->assertRedirect(route('households.index'));

        $this->assertSame(
            HousingCompatibilityStatus::RequiresRevalidation,
            $context['application']->housingPreferences()
                ->firstOrFail()
                ->compatibility_status,
        );
    }
}
