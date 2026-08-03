<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Models\ApplicationSnapshot;
use App\Services\Allocation\HousingPreferenceService;
use App\Services\Applications\ApplicationReceiptService;
use App\Services\Applications\ApplicationSnapshotService;
use App\Services\Applications\HousingPreferenceSnapshotService;
use App\Services\Candidate\HouseholdMemberService;
use App\Services\Candidate\IncomeService;
use App\Services\DocumentStandardization\DocumentDossierBuilder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class HousingPreferenceSubmissionTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_required_preferences_block_application_submission(): void
    {
        $context = $this->compatibleHousingContext();

        $this->actingAs($context['candidate'])
            ->post(route(
                'candidate.applications.submit',
                $context['application'],
            ), $this->acceptedDeclarations())
            ->assertSessionHasErrors('application');

        $this->assertSame(
            ApplicationStatus::Draft,
            $context['application']->fresh()->status,
        );
    }

    public function test_submission_revalidates_locks_and_snapshots_preferences_for_receipt_and_dossier(): void
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

        $this->actingAs($context['candidate'])
            ->post(route(
                'candidate.applications.submit',
                $context['application'],
            ), $this->acceptedDeclarations())
            ->assertRedirect(route(
                'candidate.applications.receipt',
                $context['application'],
            ));

        $application = $context['application']->fresh();
        $preference = $application->housingPreferences()->firstOrFail();
        $snapshot = $application->snapshots()
            ->where(
                'snapshot_type',
                ApplicationSnapshotType::HousingPreferences->value,
            )
            ->firstOrFail();
        $snapshotData = $snapshot->data;

        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $this->assertNotNull($preference->submitted_at);
        $this->assertNotNull($preference->locked_at);
        $this->assertSame(
            HousingCompatibilityStatus::Compatible,
            $preference->compatibility_status,
        );
        $this->assertSame(
            $application->regulatory_snapshot_id,
            $preference->regulatory_snapshot_id,
        );
        $this->assertSame(1, $snapshotData[0]['preference_order']);
        $this->assertSame(
            $unit->housingUnit->public_reference,
            $snapshotData[0]['public_reference'],
        );

        $receipt = app(ApplicationReceiptService::class)->data($application);
        $this->assertSame(
            $snapshotData,
            $receipt['housingPreferences'],
        );

        $dossier = app(DocumentDossierBuilder::class)->build($application);
        $this->assertSame(
            $snapshotData,
            $dossier['housing_preferences'],
        );

        $this->actingAs($context['candidate'])
            ->get(route('candidate.applications.receipt', $application))
            ->assertOk()
            ->assertSee('Habitações pretendidas')
            ->assertSee($unit->housingUnit->public_title);
    }

    public function test_unit_that_becomes_unavailable_blocks_submission_and_is_audited(): void
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
        $unit->forceFill([
            'status' => ContestHousingUnitStatus::Unavailable,
        ])->save();

        $this->actingAs($context['candidate'])
            ->post(route(
                'candidate.applications.submit',
                $context['application'],
            ), $this->acceptedDeclarations())
            ->assertSessionHasErrors('preferences');

        $application = $context['application']->fresh();
        $preference = $application->housingPreferences()->firstOrFail();

        $this->assertSame(ApplicationStatus::Draft, $application->status);
        $this->assertNull($preference->locked_at);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'allocations',
            'action' => 'housing_preference_rejected_on_submission',
        ]);
    }

    public function test_income_and_household_changes_invalidate_without_deleting_preferences(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        $service = app(HousingPreferenceService::class);
        $selection = [[
            'contest_housing_unit_id' => $unit->id,
            'preference_order' => 1,
        ]];
        $service->replace(
            $context['application'],
            $selection,
            $context['candidate'],
        );

        app(IncomeService::class)->update(
            $context['income'],
            [
                'household_member_id' => $context['applicant']->id,
                'income_source_id' => $context['income']->income_source_id,
                'description' => 'Rendimento atualizado em teste.',
                'monthly_amount' => '2100.00',
                'annual_amount' => '25200.00',
                'reference_year' => 2026,
                'is_current' => true,
                'is_taxable' => true,
            ],
            $context['candidate'],
        );

        $preference = $context['application']
            ->housingPreferences()
            ->firstOrFail();
        $this->assertSame(
            HousingCompatibilityStatus::RequiresRevalidation,
            $preference->compatibility_status,
        );
        $this->assertNotNull($preference->invalidated_at);
        $this->assertSame(
            'Rendimentos do agregado alterados.',
            $preference->invalidation_reason,
        );

        $service->replace(
            $context['application']->fresh(),
            $selection,
            $context['candidate'],
        );
        app(HouseholdMemberService::class)->update(
            $context['applicant']->fresh(),
            [
                'full_name' => 'Nome atualizado para teste',
                'birth_date' => today()->subYears(35)->toDateString(),
                'is_disabled' => false,
                'has_no_income' => false,
            ],
            $context['candidate'],
        );

        $preference = $context['application']
            ->housingPreferences()
            ->firstOrFail();
        $this->assertSame(
            HousingCompatibilityStatus::RequiresRevalidation,
            $preference->compatibility_status,
        );
        $this->assertNotNull($preference->invalidated_at);
        $this->assertDatabaseCount('housing_preferences', 1);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'allocations',
            'action' => 'housing_preferences_invalidated',
        ]);
    }

    public function test_draft_preview_is_in_memory_and_cannot_create_final_snapshot(): void
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
        $preview = app(HousingPreferenceSnapshotService::class)
            ->liveForApplication($context['application']->fresh());

        $this->assertCount(1, $preview);
        $this->assertDatabaseCount('application_snapshots', 0);

        $this->expectException(ValidationException::class);
        app(ApplicationSnapshotService::class)
            ->create($context['application']->fresh());
    }

    public function test_submitted_snapshot_is_idempotent_and_model_is_immutable(): void
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
        $this->actingAs($context['candidate'])
            ->post(route(
                'candidate.applications.submit',
                $context['application'],
            ), $this->acceptedDeclarations())
            ->assertRedirect();
        $application = $context['application']->fresh();
        $service = app(ApplicationSnapshotService::class);
        $first = $service->create($application);
        $second = $service->create($application->fresh());
        $snapshot = ApplicationSnapshot::query()
            ->where('application_id', $application->id)
            ->where(
                'snapshot_type',
                ApplicationSnapshotType::HousingPreferences->value,
            )
            ->firstOrFail();
        $original = $snapshot->data;

        $this->assertSame($first->modelKeys(), $second->modelKeys());
        $this->assertSame(
            $original,
            $snapshot->fresh()->data,
        );
        $this->assertSame(
            count(ApplicationSnapshotType::cases()),
            ApplicationSnapshot::query()
                ->where('application_id', $application->id)
                ->count(),
        );

        $this->expectException(LogicException::class);
        $snapshot->forceFill(['data' => ['mutated' => true]])->save();
    }

    public function test_snapshot_failure_rolls_back_without_partial_rows_or_audit(): void
    {
        $context = $this->compatibleHousingContext();
        $submittedAt = now();
        $application = $context['application'];
        $application->forceFill([
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => $submittedAt,
            'locked_at' => $submittedAt,
        ])->save();
        $application->snapshots()->create([
            'snapshot_type' => ApplicationSnapshotType::Summary,
            'data' => ['existing' => true],
        ]);
        $context['housing_situation']->delete();

        try {
            app(ApplicationSnapshotService::class)
                ->create($application->fresh());
            $this->fail('Era esperada uma falha de validação.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('application_snapshots', [
                'application_id' => $application->id,
                'snapshot_type' => ApplicationSnapshotType::Summary->value,
            ]);
            $this->assertSame(
                1,
                $application->snapshots()->count(),
            );
            $this->assertDatabaseMissing('audit_logs', [
                'auditable_type' => $application->getMorphClass(),
                'auditable_id' => $application->id,
                'action' => 'snapshot',
            ]);
        }
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
