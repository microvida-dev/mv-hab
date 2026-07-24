<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\DocumentStatus;
use App\Enums\IncomeSourceType;
use App\Models\AdhesionRegistration;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\Documents\DocumentChecklistService;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\IncomeSourceSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepeatableDocumentChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeatable_requirement_expands_into_three_slots_for_each_employment_income(): void
    {
        [
            'registration' => $registration,
            'member' => $member,
            'income' => $income,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        $checklist = app(DocumentChecklistService::class)
            ->forRegistration($registration->fresh());

        $items = collect($checklist['items'])
            ->where('required_document_id', $requirement->id)
            ->where('target_id', $income->id)
            ->values();

        $this->assertCount(3, $items);
        $this->assertSame(
            [1, 2, 3],
            $items->pluck('requirement_instance')->all(),
        );
        $this->assertSame(
            [3, 3, 3],
            $items->pluck('required_submissions')->all(),
        );
        $this->assertCount(3, $items->pluck('key')->unique());
        $this->assertTrue(
            $items->every(
                fn (array $item): bool => $item['status'] === DocumentStatus::Missing
                    && $item['missing'] === true,
            ),
        );
        $this->assertTrue(
            $items->every(
                fn (array $item): bool => str_contains(
                    (string) $item['target_label'],
                    $member->full_name,
                ),
            ),
        );
    }

    public function test_each_working_household_member_receives_three_independent_slots(): void
    {
        [
            'registration' => $registration,
            'household' => $household,
            'source' => $source,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        $secondMember = HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'full_name' => 'Segundo Elemento Trabalhador',
        ]);

        $secondIncome = IncomeRecord::factory()->create([
            'household_member_id' => $secondMember->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'income_source_id' => $source->id,
        ]);

        $checklist = app(DocumentChecklistService::class)
            ->forRegistration($registration->fresh());

        $items = collect($checklist['items'])
            ->where('required_document_id', $requirement->id)
            ->values();

        $this->assertCount(6, $items);
        $this->assertSame(
            [3, 3],
            $items
                ->groupBy('target_id')
                ->map(fn (Collection $group): int => $group->count())
                ->sort()
                ->values()
                ->all(),
        );

        $this->assertCount(
            3,
            $items->where('target_id', $secondIncome->id),
        );
    }

    public function test_same_household_member_with_two_employment_records_receives_six_slots(): void
    {
        [
            'registration' => $registration,
            'household' => $household,
            'member' => $member,
            'source' => $source,
            'income' => $firstIncome,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        $secondIncome = IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'income_source_id' => $source->id,
            'description' => 'Segundo vínculo laboral',
            'monthly_amount' => 650,
            'annual_amount' => 7800,
        ]);

        $checklist = app(DocumentChecklistService::class)
            ->forRegistration($registration->fresh());

        $items = collect($checklist['items'])
            ->where('required_document_id', $requirement->id)
            ->values();

        $this->assertCount(6, $items);

        $this->assertEqualsCanonicalizing(
            [
                $firstIncome->id,
                $secondIncome->id,
            ],
            $items
                ->pluck('target_id')
                ->unique()
                ->values()
                ->all(),
        );

        $this->assertSame(
            [3, 3],
            $items
                ->groupBy('target_id')
                ->map(
                    fn (Collection $group): int => $group->count(),
                )
                ->sort()
                ->values()
                ->all(),
        );

        $this->assertCount(
            3,
            $items->where('target_id', $firstIncome->id),
        );

        $this->assertCount(
            3,
            $items->where('target_id', $secondIncome->id),
        );
    }

    public function test_candidate_can_upload_each_slot_and_cannot_duplicate_a_slot(): void
    {
        Storage::fake('local');
        config(['document-ai.enabled' => false]);

        [
            'candidate' => $candidate,
            'registration' => $registration,
            'member' => $member,
            'income' => $income,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        $this->actingAs($candidate);

        $referencePeriods = [
            1 => now()->subMonthsNoOverflow(2)->format('Y-m'),
            2 => now()->subMonthNoOverflow()->format('Y-m'),
            3 => now()->format('Y-m'),
        ];

        foreach ([1, 2, 3] as $instance) {
            $this->post(route('candidate.documents.store'), [
                'document_type_id' => $requirement->document_type_id,
                'required_document_id' => $requirement->id,
                'income_record_id' => $income->id,
                'requirement_instance' => $instance,
                'reference_period' => $referencePeriods[$instance],
                'file' => UploadedFile::fake()->create(
                    "recibo-{$instance}.pdf",
                    100,
                    'application/pdf',
                ),
            ])->assertRedirect();
        }

        $submissions = DocumentSubmission::query()
            ->where('required_document_id', $requirement->id)
            ->where('income_record_id', $income->id)
            ->orderBy('requirement_instance')
            ->get();

        $this->assertCount(3, $submissions);
        $this->assertSame(
            [1, 2, 3],
            $submissions->pluck('requirement_instance')->all(),
        );
        $this->assertTrue(
            $submissions->every(
                fn (DocumentSubmission $submission): bool => $submission->household_member_id === $member->id
                    && $submission->adhesion_registration_id
                        === $registration->id,
            ),
        );

        $this->post(route('candidate.documents.store'), [
            'document_type_id' => $requirement->document_type_id,
            'required_document_id' => $requirement->id,
            'income_record_id' => $income->id,
            'requirement_instance' => 2,
            'reference_period' => $referencePeriods[2],
            'file' => UploadedFile::fake()->create(
                'recibo-duplicado.pdf',
                100,
                'application/pdf',
            ),
        ])->assertSessionHasErrors('requirement_instance');

        $this->assertSame(
            3,
            DocumentSubmission::query()
                ->where('required_document_id', $requirement->id)
                ->where('income_record_id', $income->id)
                ->count(),
        );
    }

    public function test_candidate_cannot_submit_an_instance_outside_the_configured_range(): void
    {
        Storage::fake('local');
        config(['document-ai.enabled' => false]);

        [
            'candidate' => $candidate,
            'income' => $income,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requirement->document_type_id,
                'required_document_id' => $requirement->id,
                'income_record_id' => $income->id,
                'requirement_instance' => 4,
                'file' => UploadedFile::fake()->create(
                    'recibo-invalido.pdf',
                    100,
                    'application/pdf',
                ),
            ])
            ->assertSessionHasErrors('requirement_instance');

        $this->assertDatabaseCount('document_submissions', 0);
    }

    /**
     * @return array{
     *     candidate: User,
     *     registration: AdhesionRegistration,
     *     household: Household,
     *     member: HouseholdMember,
     *     source: IncomeSource,
     *     income: IncomeRecord,
     *     requirement: RequiredDocument
     * }
     */
    private function employmentContext(): array
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(IncomeSourceSeeder::class);
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $registration = AdhesionRegistration::factory()
            ->registered()
            ->for($candidate)
            ->create();

        $household = Household::factory()
            ->candidate($registration)
            ->create();

        $member = HouseholdMember::factory()
            ->applicant()
            ->create([
                'household_id' => $household->id,
                'adhesion_registration_id' => $registration->id,
                'full_name' => 'Elemento Trabalhador',
            ]);

        $source = IncomeSource::query()
            ->where('code', IncomeSourceType::Employment->value)
            ->firstOrFail();

        $income = IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'income_source_id' => $source->id,
        ]);

        $requirement = RequiredDocument::query()
            ->whereHas(
                'documentType',
                fn ($query) => $query
                    ->where('code', 'recibos_vencimento'),
            )
            ->firstOrFail();

        $requirement->forceFill([
            'required_submissions' => 3,
            'reference_period_unit' => DocumentReferencePeriodUnit::Month,
            'requires_distinct_reference_periods' => true,
            'reference_period_recency' => 3,
        ])->save();

        return compact(
            'candidate',
            'registration',
            'household',
            'member',
            'source',
            'income',
            'requirement',
        );
    }
}
