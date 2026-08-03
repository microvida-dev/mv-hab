<?php

namespace Tests\Feature;

use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Models\HousingPreference;
use App\Services\Allocation\HousingPreferenceService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class Sprint52BCompleteHousingOrderingTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_application_exposes_fogos_tab_with_one_position_per_compatible_unit(): void
    {
        $context = $this->compatibleHousingContext();
        $units = collect(range(1, 5))
            ->map(fn () => $this->compatibleContestHousingUnit($context));

        $this->actingAs($context['candidate'])
            ->get(route(
                'candidate.housing-preferences.edit',
                $context['application'],
            ))
            ->assertOk()
            ->assertSee('Fogos')
            ->assertSee('Secções da candidatura')
            ->assertSee('Posição 1')
            ->assertSee('Posição 5')
            ->assertSee('5 fogos compatíveis')
            ->assertSee('Guardar ordem dos fogos')
            ->assertSee('x-model="orderedIds[4]"', false)
            ->assertSee($units->first()->housingUnit->public_reference)
            ->assertSee($units->last()->housingUnit->public_reference);

        $this->actingAs($context['candidate'])
            ->get(route(
                'candidate.applications.show',
                $context['application'],
            ))
            ->assertOk()
            ->assertSee('Fogos')
            ->assertSee(route(
                'candidate.housing-preferences.edit',
                $context['application'],
            ));
    }

    public function test_complete_order_can_exceed_legacy_configured_maximum(): void
    {
        $context = $this->compatibleHousingContext();
        $units = collect(range(1, 5))
            ->map(fn () => $this->compatibleContestHousingUnit($context));
        $payload = $units
            ->values()
            ->map(fn ($unit, int $index): array => [
                'contest_housing_unit_id' => $unit->id,
                'preference_order' => $index + 1,
            ])
            ->all();

        $this->actingAs($context['candidate'])
            ->patch(route(
                'candidate.housing-preferences.update',
                $context['application'],
            ), ['preferences' => $payload])
            ->assertRedirect();

        $preferences = $context['application']
            ->housingPreferences()
            ->orderBy('preference_order')
            ->get();

        $this->assertCount(5, $preferences);
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $preferences->pluck('preference_order')->all(),
        );
        $this->assertSame(
            $units->pluck('id')->sort()->values()->all(),
            $preferences
                ->pluck('contest_housing_unit_id')
                ->sort()
                ->values()
                ->all(),
        );
    }

    public function test_submission_revalidation_rejects_newly_compatible_omitted_unit(): void
    {
        $context = $this->compatibleHousingContext();
        $first = $this->compatibleContestHousingUnit($context);
        $second = $this->compatibleContestHousingUnit($context);
        $service = app(HousingPreferenceService::class);
        $service->replace(
            $context['application'],
            [
                [
                    'contest_housing_unit_id' => $first->id,
                    'preference_order' => 1,
                ],
                [
                    'contest_housing_unit_id' => $second->id,
                    'preference_order' => 2,
                ],
            ],
            $context['candidate'],
        );

        $this->compatibleContestHousingUnit($context);

        try {
            DB::transaction(
                fn () => $service->revalidateAndLockForSubmission(
                    $context['application']->fresh(),
                    $context['candidate'],
                    now(),
                ),
            );
            $this->fail('Era esperada uma falha por omissão de um fogo compatível.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('preferences', $exception->errors());
            $this->assertStringContainsString(
                'todos os 3 fogos compatíveis',
                $exception->errors()['preferences'][0],
            );
        }

        $this->assertSame(
            ApplicationStatus::Draft,
            $context['application']->fresh()->status,
        );
        $this->assertSame(
            0,
            $context['application']
                ->housingPreferences()
                ->whereNotNull('locked_at')
                ->count(),
        );
    }

    public function test_formal_submission_locks_complete_order_and_creates_immutable_snapshot(): void
    {
        $context = $this->compatibleHousingContext();
        $units = collect(range(1, 4))
            ->map(fn () => $this->compatibleContestHousingUnit($context));
        $payload = $units
            ->values()
            ->map(fn ($unit, int $index): array => [
                'contest_housing_unit_id' => $unit->id,
                'preference_order' => $index + 1,
            ])
            ->all();

        app(HousingPreferenceService::class)->replace(
            $context['application'],
            $payload,
            $context['candidate'],
        );

        $this->actingAs($context['candidate'])
            ->post(route(
                'candidate.applications.submit',
                $context['application'],
            ), $this->acceptedDeclarations())
            ->assertRedirect();

        $application = $context['application']->fresh();
        $preferences = $application
            ->housingPreferences()
            ->orderBy('preference_order')
            ->get();
        $snapshot = $application->snapshots()
            ->where(
                'snapshot_type',
                ApplicationSnapshotType::HousingPreferences->value,
            )
            ->firstOrFail();

        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $this->assertCount(4, $preferences);
        $this->assertTrue(
            $preferences->every(
                fn (HousingPreference $preference): bool => $preference->locked_at !== null
                    && $preference->submitted_at !== null,
            ),
        );
        $this->assertSame(
            [1, 2, 3, 4],
            collect($snapshot->data)->pluck('preference_order')->all(),
        );
        $this->assertSame(
            $units->pluck('housing_unit_id')->sort()->values()->all(),
            collect($snapshot->data)
                ->pluck('housing_unit_id')
                ->sort()
                ->values()
                ->all(),
        );

        $this->actingAs($context['candidate'])
            ->patch(route(
                'candidate.housing-preferences.update',
                $application,
            ), ['preferences' => $payload])
            ->assertForbidden();

        $this->assertSame(
            $snapshot->data,
            $snapshot->fresh()->data,
        );
    }

    /**
     * @return array<string, string>
     */
    private function acceptedDeclarations(): array
    {
        return [
            'declaration_accepted' => '1',
            'contest_rules_accepted' => '1',
            'data_processing_accepted' => '1',
            'truthfulness_accepted' => '1',
            'data_current_confirmed' => '1',
        ];
    }
}
