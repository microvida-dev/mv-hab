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
use Carbon\CarbonImmutable;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\IncomeSourceSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepeatableDocumentReferencePeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config([
            'document-ai.enabled' => false,
            'app.timezone' => 'Europe/Lisbon',
        ]);

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-07-24 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    public function test_monthly_reference_period_is_required_and_normalized_to_first_day(): void
    {
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
                'requirement_instance' => 1,
                'file' => UploadedFile::fake()->create(
                    'recibo-sem-periodo.pdf',
                    100,
                    'application/pdf',
                ),
            ])
            ->assertSessionHasErrors('reference_period');

        $this->assertDatabaseCount('document_submissions', 0);

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requirement->document_type_id,
                'required_document_id' => $requirement->id,
                'income_record_id' => $income->id,
                'requirement_instance' => 1,
                'reference_period' => '2026-06',
                'file' => UploadedFile::fake()->create(
                    'recibo-junho.pdf',
                    100,
                    'application/pdf',
                ),
            ])
            ->assertRedirect();

        $submission = DocumentSubmission::query()
            ->where('required_document_id', $requirement->id)
            ->where('income_record_id', $income->id)
            ->where('requirement_instance', 1)
            ->firstOrFail();

        $this->assertSame(
            '2026-06-01',
            $submission->reference_period?->toDateString(),
        );

        $this->assertSame(
            '2026-06-01',
            $submission->reference_period?->toDateString(),
        );

        $this->assertTrue(
            DocumentSubmission::query()
                ->whereKey($submission->id)
                ->whereDate('reference_period', '2026-06-01')
                ->exists(),
        );

        $this->actingAs($candidate)
            ->get(route('candidate.documents.show', $submission))
            ->assertOk()
            ->assertSee('Período de referência')
            ->assertSee(
                $submission->reference_period?->translatedFormat('F Y'),
            );
    }

    public function test_future_reference_period_is_rejected(): void
    {
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
                'requirement_instance' => 1,
                'reference_period' => '2026-08',
                'file' => UploadedFile::fake()->create(
                    'recibo-futuro.pdf',
                    100,
                    'application/pdf',
                ),
            ])
            ->assertSessionHasErrors('reference_period');

        $this->assertDatabaseCount('document_submissions', 0);
    }

    public function test_reference_period_outside_configured_recency_is_rejected(): void
    {
        [
            'candidate' => $candidate,
            'income' => $income,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        /*
         * A configuração 3 representa uma antiguidade máxima
         * de três meses relativamente ao mês corrente.
         *
         * Em julho de 2026:
         * - abril de 2026 é aceite;
         * - março de 2026 é rejeitado.
         */
        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requirement->document_type_id,
                'required_document_id' => $requirement->id,
                'income_record_id' => $income->id,
                'requirement_instance' => 1,
                'reference_period' => '2026-03',
                'file' => UploadedFile::fake()->create(
                    'recibo-antigo.pdf',
                    100,
                    'application/pdf',
                ),
            ])
            ->assertSessionHasErrors('reference_period');

        $this->assertDatabaseCount('document_submissions', 0);
    }

    public function test_same_income_record_cannot_use_same_month_in_two_slots(): void
    {
        [
            'candidate' => $candidate,
            'income' => $income,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        $this->uploadPayslip(
            candidate: $candidate,
            requirement: $requirement,
            income: $income,
            instance: 1,
            period: '2026-06',
            filename: 'recibo-junho-1.pdf',
        );

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requirement->document_type_id,
                'required_document_id' => $requirement->id,
                'income_record_id' => $income->id,
                'requirement_instance' => 2,
                'reference_period' => '2026-06',
                'file' => UploadedFile::fake()->create(
                    'recibo-junho-2.pdf',
                    100,
                    'application/pdf',
                ),
            ])
            ->assertSessionHasErrors('reference_period');

        $this->assertSame(
            1,
            DocumentSubmission::query()
                ->where('required_document_id', $requirement->id)
                ->where('income_record_id', $income->id)
                ->count(),
        );
    }

    public function test_different_income_records_can_use_the_same_reference_month(): void
    {
        [
            'candidate' => $candidate,
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

        $this->uploadPayslip(
            candidate: $candidate,
            requirement: $requirement,
            income: $firstIncome,
            instance: 1,
            period: '2026-06',
            filename: 'primeiro-emprego-junho.pdf',
        );

        $this->uploadPayslip(
            candidate: $candidate,
            requirement: $requirement,
            income: $secondIncome,
            instance: 1,
            period: '2026-06',
            filename: 'segundo-emprego-junho.pdf',
        );

        $this->assertSame(
            2,
            DocumentSubmission::query()
                ->where('required_document_id', $requirement->id)
                ->whereDate('reference_period', '2026-06-01')
                ->count(),
        );
    }

    public function test_replacement_can_keep_or_change_period_but_cannot_duplicate_another_slot(): void
    {
        [
            'candidate' => $candidate,
            'income' => $income,
            'requirement' => $requirement,
        ] = $this->employmentContext();

        $firstSubmission = $this->uploadPayslip(
            candidate: $candidate,
            requirement: $requirement,
            income: $income,
            instance: 1,
            period: '2026-05',
            filename: 'recibo-maio.pdf',
        );

        $this->uploadPayslip(
            candidate: $candidate,
            requirement: $requirement,
            income: $income,
            instance: 2,
            period: '2026-06',
            filename: 'recibo-junho.pdf',
        );

        $firstSubmission->forceFill([
            'status' => DocumentStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => 'Documento ilegível.',
        ])->save();

        /*
         * A própria submissão é excluída da validação,
         * pelo que pode conservar maio.
         */
        $this->actingAs($candidate)
            ->post(
                route(
                    'candidate.documents.replace.store',
                    $firstSubmission,
                ),
                [
                    'reference_period' => '2026-05',
                    'file' => UploadedFile::fake()->create(
                        'recibo-maio-v2.pdf',
                        100,
                        'application/pdf',
                    ),
                ],
            )
            ->assertRedirect(
                route(
                    'candidate.documents.show',
                    $firstSubmission,
                ),
            );

        $firstSubmission->refresh();

        $this->assertSame(
            '2026-05-01',
            $firstSubmission->reference_period?->toDateString(),
        );
        $this->assertSame(2, $firstSubmission->versions()->count());

        /*
         * Volta a colocar o documento num estado substituível
         * para testar a tentativa de utilizar o mês da posição 2.
         */
        $firstSubmission->forceFill([
            'status' => DocumentStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => 'Nova correção solicitada.',
        ])->save();

        $this->actingAs($candidate)
            ->post(
                route(
                    'candidate.documents.replace.store',
                    $firstSubmission,
                ),
                [
                    'reference_period' => '2026-06',
                    'file' => UploadedFile::fake()->create(
                        'recibo-junho-duplicado.pdf',
                        100,
                        'application/pdf',
                    ),
                ],
            )
            ->assertSessionHasErrors('reference_period');

        $firstSubmission->refresh();

        $this->assertSame(
            '2026-05-01',
            $firstSubmission->reference_period?->toDateString(),
        );
        $this->assertSame(2, $firstSubmission->versions()->count());

        /*
         * Abril está dentro da antiguidade máxima configurada
         * e ainda não é utilizado por outra posição.
         */
        $this->actingAs($candidate)
            ->post(
                route(
                    'candidate.documents.replace.store',
                    $firstSubmission,
                ),
                [
                    'reference_period' => '2026-04',
                    'file' => UploadedFile::fake()->create(
                        'recibo-abril.pdf',
                        100,
                        'application/pdf',
                    ),
                ],
            )
            ->assertRedirect(
                route(
                    'candidate.documents.show',
                    $firstSubmission,
                ),
            );

        $firstSubmission->refresh();

        $this->assertSame(
            '2026-04-01',
            $firstSubmission->reference_period?->toDateString(),
        );
        $this->assertSame(DocumentStatus::Submitted, $firstSubmission->status);
        $this->assertSame(3, $firstSubmission->versions()->count());
    }

    public function test_non_periodic_requirement_does_not_store_reference_period(): void
    {
        [
            'candidate' => $candidate,
            'member' => $member,
        ] = $this->employmentContext();

        $requiredDocument = RequiredDocument::query()
            ->whereHas(
                'documentType',
                fn ($query) => $query
                    ->where('code', 'documento_identificacao'),
            )
            ->firstOrFail();

        $this->assertNull(
            $requiredDocument->reference_period_unit,
        );

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requiredDocument->document_type_id,
                'required_document_id' => $requiredDocument->id,
                'household_member_id' => $member->id,
                'reference_period' => '2026-06',
                'file' => UploadedFile::fake()->create(
                    'identificacao.pdf',
                    100,
                    'application/pdf',
                ),
            ])
            ->assertRedirect();

        $submission = DocumentSubmission::query()
            ->where('required_document_id', $requiredDocument->id)
            ->firstOrFail();

        $this->assertNull($submission->reference_period);
    }

    private function uploadPayslip(
        User $candidate,
        RequiredDocument $requirement,
        IncomeRecord $income,
        int $instance,
        string $period,
        string $filename,
    ): DocumentSubmission {
        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requirement->document_type_id,
                'required_document_id' => $requirement->id,
                'income_record_id' => $income->id,
                'requirement_instance' => $instance,
                'reference_period' => $period,
                'file' => UploadedFile::fake()->create(
                    $filename,
                    100,
                    'application/pdf',
                ),
            ])
            ->assertRedirect();

        return DocumentSubmission::query()
            ->where('required_document_id', $requirement->id)
            ->where('income_record_id', $income->id)
            ->where('requirement_instance', $instance)
            ->firstOrFail();
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
            ->where(
                'code',
                IncomeSourceType::Employment->value,
            )
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
