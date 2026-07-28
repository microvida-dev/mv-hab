<?php

namespace Tests\Feature\Seeders;

use App\Enums\ApplicationDeclarationType;
use App\Enums\ApplicationPreferenceSource;
use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentAccessAction;
use App\Enums\DocumentStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Enums\RegulatoryContext;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationSnapshot;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\HousingPreference;
use App\Models\IncomeRecord;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\Applications\ApplicationReceiptService;
use App\Services\Applications\ApplicationSubmissionService;
use App\Services\Documents\DocumentChecklistService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCatalogSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoSubmissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MunicipalApplicationDemoSubmissionSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MVHAB-Demo-2026!';

    private const EXPECTED_DOCUMENT_COUNT = 15;

    /**
     * @var array<string, int>
     */
    private const EXPECTED_DOCUMENT_TYPE_COUNTS = [
        'alcanena_demo_identificacao_residencia' => 3,
        'alcanena_demo_nif' => 3,
        'alcanena_demo_nota_liquidacao_irs' => 1,
        'alcanena_demo_situacao_regular_at' => 1,
        'alcanena_demo_situacao_regular_iss' => 1,
        'recibos_vencimento' => 6,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';

        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            '2026-07-27',
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            self::PASSWORD,
        );

        /*
         * O seeder deve suspender localmente a análise IA durante a criação
         * dos documentos e restaurar esta configuração no final.
         */
        config()->set('document-ai.enabled', true);

        Storage::fake('local');
        Queue::fake();

        CarbonImmutable::setTestNow(
            $this->referenceDate()->setTime(12, 0),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_orchestrator_creates_complete_private_document_checklist(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $submissions = $this->documentSubmissions($application);
        $checklist = app(DocumentChecklistService::class)
            ->forApplication($application->fresh());

        $this->assertSame(
            [
                'total_required' => self::EXPECTED_DOCUMENT_COUNT,
                'missing' => 0,
                'submitted' => self::EXPECTED_DOCUMENT_COUNT,
                'validated' => 0,
                'rejected' => 0,
                'percentage' => 100,
            ],
            $checklist['summary'],
        );

        $this->assertCount(
            self::EXPECTED_DOCUMENT_COUNT,
            $submissions,
        );

        $actualCounts = $submissions
            ->countBy(
                static fn (DocumentSubmission $submission): string => (string) $submission->documentType?->code,
            )
            ->sortKeys()
            ->all();

        $this->assertSame(
            collect(self::EXPECTED_DOCUMENT_TYPE_COUNTS)
                ->sortKeys()
                ->all(),
            $actualCounts,
        );

        foreach ($submissions as $submission) {
            $this->assertSame(
                $application->id,
                $submission->application_id,
            );
            $this->assertSame(
                $application->user_id,
                $submission->user_id,
            );
            $this->assertSame(
                $application->user_id,
                $submission->submitted_by,
            );
            $this->assertSame(
                DocumentStatus::Submitted,
                $submission->status,
            );
            $this->assertNotNull($submission->submitted_at);
            $this->assertNotNull($submission->required_document_id);
            $this->assertNotNull($submission->current_version_id);
            $this->assertSame(1, $submission->versions->count());

            $version = $submission->currentVersion;

            $this->assertInstanceOf(DocumentVersion::class, $version);
            $this->assertSame(
                $submission->id,
                $version->document_submission_id,
            );
            $this->assertSame(1, $version->version_number);
            $this->assertSame('local', $version->storage_disk);
            $this->assertSame('application/pdf', $version->mime_type);
            $this->assertSame(
                DocumentStatus::Submitted,
                $version->status_at_upload,
            );
            $this->assertSame(
                $application->user_id,
                $version->uploaded_by,
            );
            $this->assertNotNull($version->uploaded_at);
            $this->assertGreaterThan(0, $version->file_size);

            $this->assertMatchesRegularExpression(
                '/^demo-document-\d{3}\.pdf$/',
                $version->original_filename,
            );
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-'
                    .'[89ab][0-9a-f]{3}-[0-9a-f]{12}\.pdf$/',
                $version->stored_filename,
            );

            $this->assertStringStartsWith(
                'documents/'
                    .$application->adhesion_registration_id
                    .'/'.$submission->id.'/1/',
                $version->storage_path,
            );

            $this->assertPrivateFileHasExpectedIntegrity($version);
            $this->assertPathContainsNoCandidatePii($version);
        }

        $submissionIds = $submissions->modelKeys();

        $this->assertSame(
            self::EXPECTED_DOCUMENT_COUNT,
            DB::table('document_access_logs')
                ->whereIn('document_submission_id', $submissionIds)
                ->where(
                    'action',
                    DocumentAccessAction::Upload->value,
                )
                ->count(),
        );

        $this->assertDatabaseCount('document_ai_analyses', 0);
        Queue::assertNothingPushed();

        /*
         * A configuração global deve ser restaurada após o seeder.
         */
        $this->assertTrue(
            (bool) config('document-ai.enabled'),
        );
    }

    public function test_seeder_creates_two_independent_sets_of_three_payslips(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $requirement = RequiredDocument::query()
            ->where('program_id', $application->program_id)
            ->where('contest_id', $application->contest_id)
            ->whereHas(
                'documentType',
                static fn ($query) => $query->where(
                    'code',
                    'recibos_vencimento',
                ),
            )
            ->sole();

        $this->assertSame(3, $requirement->required_submissions);
        $this->assertTrue(
            $requirement->requires_distinct_reference_periods,
        );
        $this->assertSame(3, $requirement->reference_period_recency);

        $incomeRecords = IncomeRecord::query()
            ->where('household_id', $application->household_id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $incomeRecords);

        $payslips = DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->where('required_document_id', $requirement->id)
            ->orderBy('income_record_id')
            ->orderBy('requirement_instance')
            ->get();

        $this->assertCount(6, $payslips);

        foreach ($incomeRecords as $incomeRecord) {
            $incomePayslips = $payslips
                ->where('income_record_id', $incomeRecord->id)
                ->values();

            $this->assertCount(3, $incomePayslips);
            $this->assertSame(
                [1, 2, 3],
                $incomePayslips
                    ->pluck('requirement_instance')
                    ->map(static fn ($value): int => (int) $value)
                    ->all(),
            );
            $this->assertSame(
                ['2026-07-01', '2026-06-01', '2026-05-01'],
                $incomePayslips
                    ->map(
                        static fn (
                            DocumentSubmission $submission,
                        ): ?string => $submission
                            ->reference_period
                            ?->toDateString(),
                    )
                    ->all(),
            );
            $this->assertSame(
                3,
                $incomePayslips
                    ->pluck('reference_period')
                    ->map(
                        static fn ($period): ?string => $period?->toDateString(),
                    )
                    ->unique()
                    ->count(),
            );
            $this->assertSame(
                [$incomeRecord->household_member_id],
                $incomePayslips
                    ->pluck('household_member_id')
                    ->unique()
                    ->values()
                    ->all(),
            );
        }
    }

    public function test_application_is_formally_submitted_with_declarations_history_and_regulatory_snapshot(): void
    {
        $this->seedDemo();

        $application = $this->application()->fresh();

        $this->assertSame(
            ApplicationStatus::Submitted,
            $application->status,
        );
        $this->assertNotEmpty($application->application_number);
        $this->assertNotNull($application->submitted_at);
        $this->assertNotNull($application->locked_at);
        $this->assertTrue(
            $application->submitted_at?->equalTo(
                $application->locked_at,
            ) ?? false,
        );

        $this->assertTrue($application->declaration_accepted);
        $this->assertNotNull(
            $application->declaration_accepted_at,
        );
        $this->assertTrue($application->contest_rules_accepted);
        $this->assertNotNull(
            $application->contest_rules_accepted_at,
        );
        $this->assertTrue(
            $application->data_processing_accepted,
        );
        $this->assertNotNull(
            $application->data_processing_accepted_at,
        );
        $this->assertTrue($application->truthfulness_accepted);
        $this->assertNotNull(
            $application->truthfulness_accepted_at,
        );
        $this->assertTrue($application->data_current_confirmed);
        $this->assertNotNull(
            $application->data_current_confirmed_at,
        );

        $declarations = $application->declarations()
            ->orderBy('declaration_type')
            ->get();

        $expectedTypes = collect(
            ApplicationDeclarationType::cases(),
        )
            ->map(
                static fn (
                    ApplicationDeclarationType $type,
                ): string => $type->value,
            )
            ->sort()
            ->values()
            ->all();

        $actualTypes = $declarations
            ->map(
                static fn ($declaration): string => self::enumValue(
                    $declaration->declaration_type,
                ),
            )
            ->sort()
            ->values()
            ->all();

        $this->assertCount(5, $declarations);
        $this->assertSame($expectedTypes, $actualTypes);

        foreach ($declarations as $declaration) {
            $this->assertTrue($declaration->accepted);
            $this->assertNotNull($declaration->accepted_at);
            $this->assertSame(
                ApplicationSubmissionService::DECLARATION_VERSION,
                $declaration->text_version,
            );
        }

        $histories = DB::table('application_status_histories')
            ->where('application_id', $application->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $histories);
        $this->assertNull($histories[0]->from_status);
        $this->assertSame(
            ApplicationStatus::Draft->value,
            $histories[0]->to_status,
        );
        $this->assertSame(
            ApplicationStatus::Draft->value,
            $histories[1]->from_status,
        );
        $this->assertSame(
            ApplicationStatus::Submitted->value,
            $histories[1]->to_status,
        );

        $this->assertNotNull($application->regulatory_snapshot_id);

        $regulatorySnapshot = DB::table('regulatory_snapshots')
            ->where(
                'source_type',
                $application->getMorphClass(),
            )
            ->where('source_id', $application->id)
            ->where(
                'context',
                RegulatoryContext::ApplicationSubmission->value,
            )
            ->sole();

        $this->assertSame(
            'application_submission',
            $regulatorySnapshot->origin,
        );
        $this->assertSame(
            $application->regulatory_snapshot_id,
            $regulatorySnapshot->id,
        );
        $this->assertNotEmpty($regulatorySnapshot->checksum);
        $this->assertNotNull($regulatorySnapshot->locked_at);
        $this->assertNotNull(
            $regulatorySnapshot->regulatory_profile_id,
        );

        $this->assertSame(
            1,
            DB::table('audit_logs')
                ->where(
                    'auditable_type',
                    $application->getMorphClass(),
                )
                ->where('auditable_id', $application->id)
                ->where('module', 'applications')
                ->where('action', 'submit')
                ->count(),
        );
    }

    public function test_submission_creates_exact_application_document_links_and_immutable_snapshots(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $applicationDocuments = ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->with([
                'documentSubmission.currentVersion',
                'documentType',
            ])
            ->orderBy('id')
            ->get();

        $this->assertCount(
            self::EXPECTED_DOCUMENT_COUNT,
            $applicationDocuments,
        );
        $this->assertSame(
            self::EXPECTED_DOCUMENT_COUNT,
            $applicationDocuments
                ->pluck('document_submission_id')
                ->unique()
                ->count(),
        );

        foreach ($applicationDocuments as $document) {
            $this->assertTrue($document->is_required);
            $this->assertSame(
                DocumentStatus::Submitted,
                $document->status_at_submission,
            );
            $this->assertSame(
                $document->document_type_id,
                $document->documentSubmission?->document_type_id,
            );
        }

        $snapshots = ApplicationSnapshot::query()
            ->where('application_id', $application->id)
            ->orderBy('snapshot_type')
            ->get();

        $expectedTypes = collect(ApplicationSnapshotType::cases())
            ->map(
                static fn (
                    ApplicationSnapshotType $type,
                ): string => $type->value,
            )
            ->sort()
            ->values()
            ->all();

        $actualTypes = $snapshots
            ->map(
                static fn (
                    ApplicationSnapshot $snapshot,
                ): string => self::enumValue(
                    $snapshot->snapshot_type,
                ),
            )
            ->sort()
            ->values()
            ->all();

        $this->assertCount(8, $snapshots);
        $this->assertSame($expectedTypes, $actualTypes);

        $documentsSnapshot = $this->snapshotData(
            $application,
            ApplicationSnapshotType::Documents,
        );

        $this->assertCount(
            self::EXPECTED_DOCUMENT_COUNT,
            $documentsSnapshot,
        );

        foreach ($documentsSnapshot as $row) {
            $this->assertArrayHasKey(
                'document_submission_id',
                $row,
            );
            $this->assertArrayHasKey('required_document_id', $row);
            $this->assertArrayHasKey('target_type', $row);
            $this->assertArrayHasKey('target_id', $row);
            $this->assertArrayHasKey('target_label', $row);
            $this->assertArrayHasKey(
                'requirement_instance',
                $row,
            );
            $this->assertArrayHasKey(
                'required_submissions',
                $row,
            );
            $this->assertArrayHasKey('position_label', $row);
            $this->assertArrayHasKey('reference_period', $row);
            $this->assertArrayHasKey(
                'document_type_code',
                $row,
            );
            $this->assertArrayHasKey(
                'status_at_submission',
                $row,
            );
            $this->assertArrayHasKey('version_number', $row);
            $this->assertArrayHasKey('original_filename', $row);
            $this->assertArrayHasKey('mime_type', $row);
            $this->assertArrayHasKey('checksum', $row);
            $this->assertSame(
                DocumentStatus::Submitted->value,
                $row['status_at_submission'],
            );
        }

        $payslipRows = collect($documentsSnapshot)
            ->where(
                'document_type_code',
                'recibos_vencimento',
            )
            ->values();

        $this->assertCount(6, $payslipRows);
        $this->assertSame(
            ['1/3', '2/3', '3/3'],
            $payslipRows
                ->pluck('position_label')
                ->unique()
                ->sort()
                ->values()
                ->all(),
        );

        $summary = $this->snapshotData(
            $application,
            ApplicationSnapshotType::Summary,
        );

        $this->assertSame(
            $application->application_number,
            $summary['application_number'] ?? null,
        );
        $this->assertSame(
            MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
            $summary['contest_code'] ?? null,
        );
        $this->assertSame(3, $summary['member_count'] ?? null);
        $this->assertSame(
            self::EXPECTED_DOCUMENT_COUNT,
            $summary['document_count'] ?? null,
        );
    }

    public function test_preferences_are_locked_and_receipt_uses_final_snapshots(): void
    {
        $this->seedDemo();

        $application = $this->application()->fresh();
        $candidate = $this->candidate();

        $this->assertSame(
            ApplicationPreferenceSource::Official,
            $application->preference_source,
        );

        $preferences = HousingPreference::query()
            ->where('application_id', $application->id)
            ->with('housingUnit')
            ->orderBy('preference_order')
            ->get();

        $this->assertCount(3, $preferences);
        $this->assertSame(
            [1, 2, 3],
            $preferences->pluck('preference_order')->all(),
        );
        $this->assertSame(
            [
                'ALC-DEMO-APP-T2-01',
                'ALC-DEMO-APP-T2-02',
                'ALC-DEMO-APP-T2-03',
            ],
            $preferences->pluck('housingUnit.code')->all(),
        );

        foreach ($preferences as $preference) {
            $this->assertSame(
                HousingCompatibilityStatus::Compatible,
                $preference->compatibility_status,
            );
            $this->assertNull($preference->invalidated_at);
            $this->assertNotNull($preference->submitted_at);
            $this->assertNotNull($preference->locked_at);
            $this->assertTrue(
                $preference->submitted_at?->equalTo(
                    $application->submitted_at,
                ) ?? false,
            );
            $this->assertTrue(
                $preference->locked_at?->equalTo(
                    $application->locked_at,
                ) ?? false,
            );
        }

        $preferenceSnapshot = $this->snapshotData(
            $application,
            ApplicationSnapshotType::HousingPreferences,
        );

        $this->assertCount(3, $preferenceSnapshot);
        $this->assertSame(
            [1, 2, 3],
            collect($preferenceSnapshot)
                ->pluck('preference_order')
                ->all(),
        );
        $this->assertSame(
            ['housing_preferences'],
            collect($preferenceSnapshot)
                ->pluck('source')
                ->unique()
                ->values()
                ->all(),
        );
        $this->assertSame(
            [
                'ALC-DEMO-APP-T2-01',
                'ALC-DEMO-APP-T2-02',
                'ALC-DEMO-APP-T2-03',
            ],
            collect($preferenceSnapshot)->pluck('code')->all(),
        );

        $receipt = app(ApplicationReceiptService::class)
            ->data($application->fresh());

        $this->assertSame(
            $application->application_number,
            $receipt['summary']['application_number'] ?? null,
        );
        $this->assertSame(
            $preferenceSnapshot,
            $receipt['housingPreferences'],
        );

        $this->actingAs($candidate)
            ->get(
                route(
                    'candidate.applications.receipt',
                    $application,
                ),
            )
            ->assertOk()
            ->assertSee($application->application_number)
            ->assertSee('Habitações pretendidas');
    }

    public function test_submission_scope_excludes_reviews_visits_exports_and_ai(): void
    {
        $this->seedDemo();

        $this->assertDatabaseCount('document_reviews', 0);
        $this->assertDatabaseCount('property_inspections', 0);
        $this->assertDatabaseCount('application_reports', 0);
        $this->assertDatabaseCount('document_dossiers', 0);
        $this->assertDatabaseCount('eligibility_checks', 0);
        $this->assertDatabaseCount('application_scores', 0);
        $this->assertDatabaseCount('document_ai_analyses', 0);

        Queue::assertNothingPushed();
    }

    public function test_complete_orchestrator_is_idempotent_after_formal_submission(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $first = $this->stableState($application);
        $firstSubmitAuditCount = $this->submitAuditCount(
            $application,
        );

        $this->seedDemo();

        $application = $this->application();
        $second = $this->stableState($application);

        $this->assertSame($first, $second);
        $this->assertSame(
            $firstSubmitAuditCount,
            $this->submitAuditCount($application),
        );
        $this->assertSame(1, $firstSubmitAuditCount);
        $this->assertSame(
            ApplicationStatus::Submitted,
            $application->status,
        );
        $this->assertDatabaseCount('document_ai_analyses', 0);
        Queue::assertNothingPushed();
    }

    private function seedDemo(): void
    {
        $this->assertTrue(
            class_exists(
                MunicipalApplicationDemoSubmissionSeeder::class,
            ),
            'Falta implementar MunicipalApplicationDemoSubmissionSeeder.',
        );

        $this->seed(MunicipalApplicationDemoSeeder::class);
    }

    private function application(): Application
    {
        return Application::query()
            ->whereHas(
                'user',
                static fn ($query) => $query->where(
                    'email',
                    MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
                ),
            )
            ->whereHas(
                'contest',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
                ),
            )
            ->sole();
    }

    private function candidate(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            )
            ->sole();
    }

    /**
     * @return Collection<int, DocumentSubmission>
     */
    private function documentSubmissions(
        Application $application,
    ): Collection {
        return DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->with([
                'documentType',
                'requiredDocument',
                'currentVersion',
                'versions',
            ])
            ->orderBy('id')
            ->get();
    }

    private function assertPrivateFileHasExpectedIntegrity(
        DocumentVersion $version,
    ): void {
        Storage::disk($version->storage_disk)
            ->assertExists($version->storage_path);

        $contents = Storage::disk($version->storage_disk)
            ->get($version->storage_path);

        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertSame(
            hash('sha256', $contents),
            $version->checksum,
        );
        $this->assertSame(
            strlen($contents),
            $version->file_size,
        );
    }

    private function assertPathContainsNoCandidatePii(
        DocumentVersion $version,
    ): void {
        $haystack = mb_strtolower(
            implode(' ', [
                $version->original_filename,
                $version->stored_filename,
                $version->storage_path,
            ]),
        );

        foreach (['joao', 'miguel', 'ferreira', 'ana', 'ines'] as $term) {
            $this->assertStringNotContainsString(
                $term,
                $haystack,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotData(
        Application $application,
        ApplicationSnapshotType $type,
    ): array {
        $snapshot = ApplicationSnapshot::query()
            ->where('application_id', $application->id)
            ->where('snapshot_type', $type->value)
            ->sole();

        $data = $snapshot->data;

        $this->assertIsArray($data);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function stableState(
        Application $application,
    ): array {
        $submissions = $this->documentSubmissions($application);
        $versions = DocumentVersion::query()
            ->whereIn(
                'document_submission_id',
                $submissions->modelKeys(),
            )
            ->orderBy('id')
            ->get();

        return [
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'application_number' => $application->application_number,
                'status' => self::enumValue($application->status),
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'locked_at' => $application->locked_at?->toIso8601String(),
                'regulatory_snapshot_id' => $application->regulatory_snapshot_id,
            ],
            'submissions' => $submissions
                ->map(
                    static fn (
                        DocumentSubmission $submission,
                    ): array => [
                        'id' => $submission->id,
                        'required_document_id' => $submission->required_document_id,
                        'requirement_instance' => $submission->requirement_instance,
                        'reference_period' => $submission
                            ->reference_period
                            ?->toDateString(),
                        'household_member_id' => $submission->household_member_id,
                        'income_record_id' => $submission->income_record_id,
                        'household_id' => $submission->household_id,
                        'application_id' => $submission->application_id,
                        'current_version_id' => $submission->current_version_id,
                    ],
                )
                ->all(),
            'versions' => $versions
                ->map(
                    static fn (
                        DocumentVersion $version,
                    ): array => [
                        'id' => $version->id,
                        'document_submission_id' => $version->document_submission_id,
                        'version_number' => $version->version_number,
                        'storage_path' => $version->storage_path,
                        'checksum' => $version->checksum,
                    ],
                )
                ->all(),
            'application_documents' => ApplicationDocument::query()
                ->where(
                    'application_id',
                    $application->id,
                )
                ->orderBy('id')
                ->pluck('document_submission_id', 'id')
                ->all(),
            'declarations' => $application
                ->declarations()
                ->orderBy('id')
                ->get()
                ->map(
                    static fn ($declaration): array => [
                        'id' => $declaration->id,
                        'type' => self::enumValue(
                            $declaration->declaration_type,
                        ),
                        'accepted_at' => $declaration
                            ->accepted_at
                            ?->toIso8601String(),
                        'text_version' => $declaration->text_version,
                    ],
                )
                ->all(),
            'snapshots' => ApplicationSnapshot::query()
                ->where('application_id', $application->id)
                ->orderBy('id')
                ->get()
                ->map(
                    static fn (
                        ApplicationSnapshot $snapshot,
                    ): array => [
                        'id' => $snapshot->id,
                        'type' => self::enumValue(
                            $snapshot->snapshot_type,
                        ),
                        'data' => $snapshot->data,
                    ],
                )
                ->all(),
            'preferences' => HousingPreference::query()
                ->where('application_id', $application->id)
                ->orderBy('preference_order')
                ->get()
                ->map(
                    static fn (
                        HousingPreference $preference,
                    ): array => [
                        'id' => $preference->id,
                        'order' => $preference->preference_order,
                        'submitted_at' => $preference
                            ->submitted_at
                            ?->toIso8601String(),
                        'locked_at' => $preference
                            ->locked_at
                            ?->toIso8601String(),
                    ],
                )
                ->all(),
            'upload_access_logs' => DB::table(
                'document_access_logs',
            )
                ->whereIn(
                    'document_submission_id',
                    $submissions->modelKeys(),
                )
                ->where(
                    'action',
                    DocumentAccessAction::Upload->value,
                )
                ->orderBy('id')
                ->pluck('document_version_id', 'id')
                ->all(),
            'files' => $versions
                ->mapWithKeys(
                    static fn (
                        DocumentVersion $version,
                    ): array => [
                        $version->storage_path => hash(
                            'sha256',
                            Storage::disk(
                                $version->storage_disk,
                            )->get($version->storage_path),
                        ),
                    ],
                )
                ->all(),
        ];
    }

    private function submitAuditCount(
        Application $application,
    ): int {
        return DB::table('audit_logs')
            ->where(
                'auditable_type',
                $application->getMorphClass(),
            )
            ->where('auditable_id', $application->id)
            ->where('module', 'applications')
            ->where('action', 'submit')
            ->count();
    }

    private function referenceDate(): CarbonImmutable
    {
        return CarbonImmutable::create(
            2026,
            7,
            27,
            timezone: 'Europe/Lisbon',
        );
    }

    private static function enumValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
