<?php

namespace Database\Seeders\Demo;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationResultExportStage;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationReviewPublicationStatus;
use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewType;
use App\Enums\ApplicationStatus;
use App\Enums\CommunicationChannel;
use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionRequiredAction;
use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\CorrectionRevalidationItemType;
use App\Enums\ExportScope;
use App\Enums\FeatureKey;
use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationType;
use App\Enums\ProgramStatus;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Models\AdhesionRegistration;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublication;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\Contest;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\CorrectionSubmissionReceipt;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\DocumentSubmissionContextResolver;
use App\Services\Entitlements\MunicipalityEntitlementService;
use App\Services\Notifications\OfficialNotificationService;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use App\Support\Demo\MunicipalApplicationDemoContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Seeders\ReportDefinitionSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Ramsey\Uuid\Uuid;

final class MunicipalApplicationDemoProgram53Seeder extends Seeder
{
    public const CONTROL_MUNICIPALITY_CODE = 'MVHAB-DEMO-ISOLAMENTO';

    public const CONTROL_CONTEST_CODE = 'ISO-DEMO-CAND-01-2026';

    public const CONTROL_ANALYST_EMAIL = 'analista.isolamento.demo@mvhab.local';

    public const CONTROL_CANDIDATE_EMAIL = 'candidato.isolamento.demo@mvhab.local';

    public const NO_RESPONSE_CANDIDATE_EMAIL = 'candidato.sem.resposta.demo@mvhab.local';

    public const NO_RESPONSE_APPLICATION_NUMBER = 'CAND-DEMO-SEM-RESPOSTA-2026';

    private const CONTROL_ROLE_NAME = 'demo_isolamento_analista_candidaturas';

    private const TEMPORAL_EXPORT_TOKENS = [
        'sealed' => 'program53-demo-sealed-batch-v1',
        'delta' => 'program53-demo-delta-batches-v1',
    ];

    public function run(): void
    {
        $context = app(MunicipalApplicationDemoContext::class);
        $context->assertSeederAllowed();
        $this->call(ReportDefinitionSeeder::class);

        $reference = $context->referenceDate();
        $primary = $this->primaryContext();
        $this->alignProcessingDeadlines($primary['contest'], $reference);

        $noResponse = $this->ensureSyntheticApplication(
            municipality: $primary['municipality'],
            program: $primary['program'],
            contest: $primary['contest'],
            analyst: $primary['analyst'],
            candidateEmail: self::NO_RESPONSE_CANDIDATE_EMAIL,
            candidateName: 'Candidato Sem Resposta Demo',
            applicationNumber: self::NO_RESPONSE_APPLICATION_NUMBER,
            processNumber: 'PROC-DEMO-SEM-RESPOSTA-2026',
            reference: $reference->subDays(25),
        );

        $initialBatch = $this->ensureBatch(
            municipality: $primary['municipality'],
            contest: $primary['contest'],
            actor: $primary['analyst'],
            cycle: ApplicationReviewBatchCycle::InitialReview,
            sequence: 1,
            sealedAt: $reference->subDays(17)->setTime(11, 0),
            reason: 'Fecho inicial fictício do ciclo documental do Programa 53.',
            entries: [
                $this->primaryInitialEntry($primary),
                $this->noResponseEntry($noResponse),
            ],
        );
        $initialPublication = $this->ensurePublication(
            $initialBatch,
            $primary['analyst'],
            $reference->subDays(16)->setTime(10, 0),
            'Publicação inicial fictícia sem efeitos administrativos.',
        );

        $primaryInitialResult = $initialPublication->results()
            ->where('application_id', $primary['application']->id)
            ->sole();
        $noResponseResult = $initialPublication->results()
            ->where('application_id', $noResponse['application']->id)
            ->sole();
        $this->alignPrimaryCorrection(
            $primary,
            $primaryInitialResult,
            $reference,
        );
        $receipt = $this->ensureSubmissionReceipt(
            $primary,
            $reference->subDays(9)->setTime(15, 30),
        );
        $this->ensureNoResponseCorrection(
            $noResponse,
            $noResponseResult,
            $primary['analyst'],
            $reference,
        );

        $revalidationBatch = $this->ensureBatch(
            municipality: $primary['municipality'],
            contest: $primary['contest'],
            actor: $primary['analyst'],
            cycle: ApplicationReviewBatchCycle::Revalidation,
            sequence: 2,
            sealedAt: $reference->subDays(6)->setTime(11, 0),
            reason: 'Segunda análise fictícia após aperfeiçoamento integral.',
            entries: [
                $this->primaryRevalidationEntry($primary, $receipt),
            ],
            correctionRequest: $primary['correction_request'],
        );
        $revalidationPublication = $this->ensurePublication(
            $revalidationBatch,
            $primary['analyst'],
            $reference->subDays(5)->setTime(10, 0),
            'Publicação fictícia da revalidação sem decisão automática.',
        );
        $revalidationResult = $revalidationPublication->results()
            ->where('application_id', $primary['application']->id)
            ->sole();
        $this->alignRevalidationProjection(
            $primary,
            $revalidationResult,
            $reference,
        );

        $control = $this->ensureControlMunicipality($context, $reference);
        $controlBatch = $this->ensureBatch(
            municipality: $control['municipality'],
            contest: $control['contest'],
            actor: $control['analyst'],
            cycle: ApplicationReviewBatchCycle::InitialReview,
            sequence: 1,
            sealedAt: $reference->subDays(4)->setTime(11, 0),
            reason: 'Lote fictício do Município de controlo de isolamento.',
            entries: [
                $this->completeEntry(
                    $control['application'],
                    $control['process'],
                ),
            ],
        );
        $this->ensurePublication(
            $controlBatch,
            $control['analyst'],
            $reference->subDays(3)->setTime(10, 0),
            'Publicação fictícia isolada do Município de controlo.',
        );

        $this->ensureTemporalExports(
            $primary,
            $initialBatch,
            $revalidationBatch,
            $reference,
        );
    }

    /**
     * @return array{
     *     municipality: Municipality,
     *     program: Program,
     *     contest: Contest,
     *     analyst: User,
     *     candidate: User,
     *     application: Application,
     *     process: AdministrativeProcess,
     *     initial_review: ApplicationReview,
     *     correction_review: ApplicationReview,
     *     correction_request: CorrectionRequest,
     *     correction_response: CorrectionResponse
     * }
     */
    private function primaryContext(): array
    {
        $municipality = Municipality::query()
            ->where('code', MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE)
            ->sole();
        $contest = Contest::query()
            ->where('code', MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE)
            ->with('program')
            ->sole();
        $program = $contest->program;

        if (! $program instanceof Program) {
            throw new LogicException('O concurso demo não possui programa municipal.');
        }

        $analyst = User::query()
            ->where('email', MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL)
            ->where('municipality_id', $municipality->id)
            ->sole();
        $candidate = User::query()
            ->where('email', MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL)
            ->where('municipality_id', $municipality->id)
            ->sole();
        $application = Application::query()
            ->where('contest_id', $contest->id)
            ->where('user_id', $candidate->id)
            ->sole();
        $process = AdministrativeProcess::query()
            ->where('application_id', $application->id)
            ->sole();
        $initialReview = ApplicationReview::query()
            ->where('application_id', $application->id)
            ->where('review_type', ApplicationReviewType::Documental->value)
            ->sole();
        $correctionReview = ApplicationReview::query()
            ->where('application_id', $application->id)
            ->where('review_type', ApplicationReviewType::CorrectionResponse->value)
            ->sole();
        $correctionRequest = CorrectionRequest::query()
            ->where('application_id', $application->id)
            ->sole();
        $correctionResponse = CorrectionResponse::query()
            ->where('correction_request_id', $correctionRequest->id)
            ->sole();

        return compact(
            'municipality',
            'program',
            'contest',
            'analyst',
            'candidate',
            'application',
            'process',
        ) + [
            'initial_review' => $initialReview,
            'correction_review' => $correctionReview,
            'correction_request' => $correctionRequest,
            'correction_response' => $correctionResponse,
        ];
    }

    private function alignProcessingDeadlines(
        Contest $contest,
        CarbonImmutable $reference,
    ): void {
        $schedule = [
            ContestDeadlineType::Applications->value => [
                $reference->subDays(30)->setTime(9, 0),
                $reference->subDays(24)->setTime(17, 0),
                10,
            ],
            ContestDeadlineType::Review->value => [
                $reference->subDays(23)->setTime(9, 0),
                $reference->subDays(18)->setTime(17, 0),
                20,
            ],
            ContestDeadlineType::Corrections->value => [
                $reference->subDays(17)->setTime(9, 0),
                $reference->subDays(8)->setTime(17, 0),
                30,
            ],
            ContestDeadlineType::Revalidation->value => [
                $reference->subDays(7)->setTime(9, 0),
                $reference->addDays(2)->setTime(17, 0),
                40,
            ],
        ];

        foreach ($schedule as $type => [$startsAt, $endsAt, $sortOrder]) {
            $deadline = $contest->deadlines()->where('type', $type)->first();
            if ($deadline === null) {
                $deadline = $contest->deadlines()->make();
            }
            $deadline->forceFill([
                'type' => $type,
                'label' => ContestDeadlineType::from($type)->defaultLabel(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'description' => 'Prazo fictício e exclusivo do cenário demo do Programa 53.',
                'sort_order' => $sortOrder,
            ])->save();
        }

        $contest->forceFill([
            'opens_at' => $schedule[ContestDeadlineType::Applications->value][0],
            'closes_at' => $schedule[ContestDeadlineType::Applications->value][1],
        ])->save();
    }

    /**
     * @return array{application: Application, process: AdministrativeProcess, candidate: User}
     */
    private function ensureSyntheticApplication(
        Municipality $municipality,
        Program $program,
        Contest $contest,
        User $analyst,
        string $candidateEmail,
        string $candidateName,
        string $applicationNumber,
        string $processNumber,
        CarbonImmutable $reference,
    ): array {
        $candidate = User::query()->firstOrNew(['email' => $candidateEmail]);
        if ($candidate->exists && (int) $candidate->municipality_id !== (int) $municipality->id) {
            throw new LogicException('Um candidato demo sintético pertence a outro Município.');
        }
        $candidate->forceFill([
            'municipality_id' => $municipality->id,
            'name' => $candidateName,
            'password' => app(MunicipalApplicationDemoContext::class)->userPassword(),
            'status' => 'active',
            'email_verified_at' => $reference,
            'mfa_required' => false,
            'internal_notes' => 'Conta fictícia e sem efeitos administrativos.',
        ])->save();
        $candidateRole = Role::query()
            ->where('name', 'candidate')
            ->where('is_system', true)
            ->sole();
        $candidate->roles()->sync([$candidateRole->id]);

        $registration = AdhesionRegistration::withTrashed()
            ->firstOrNew(['email' => $candidateEmail]);
        $registration->forceFill([
            'user_id' => $candidate->id,
            'status' => AdhesionRegistrationStatus::Registered,
            'full_name' => $candidateName,
            'phone' => null,
            'mobile_phone' => null,
            'document_type' => 'Documento fictício',
            'document_number' => 'DEMO-'.substr(hash('sha256', $candidateEmail), 0, 12),
            'document_valid_until' => $reference->addYears(5),
            'nif' => 'DEMO-'.substr(hash('sha256', 'nif:'.$candidateEmail), 0, 9),
            'birth_date' => $reference->subYears(35),
            'nationality' => 'Portuguesa',
            'address' => 'Morada fictícia não identificável',
            'postal_code' => '0000-000',
            'city' => 'Localidade Demo',
            'parish' => 'Freguesia Demo',
            'municipality' => $municipality->name,
            'wants_email_notifications' => true,
            'wants_sms_notifications' => false,
            'wants_postal_notifications' => false,
            'accepts_terms' => true,
            'accepts_data_processing' => true,
            'accepted_terms_at' => $reference,
            'accepted_data_processing_at' => $reference,
            'submitted_at' => $reference,
            'deleted_at' => null,
        ])->save();

        $household = Household::withTrashed()->firstOrNew([
            'adhesion_registration_id' => $registration->id,
        ]);
        $household->forceFill([
            'municipality_id' => $municipality->id,
            'citizen_id' => null,
            'name' => 'Agregado fictício de '.$candidateName,
            'household_type' => 'single_person',
            'monthly_income' => '1000.00',
            'members_count' => 1,
            'notes' => 'Agregado mínimo para controlo do cenário demo.',
            'deleted_at' => null,
        ])->save();

        $housing = CurrentHousingSituation::withTrashed()->firstOrNew([
            'adhesion_registration_id' => $registration->id,
        ]);
        $housing->forceFill([
            'housing_status' => HousingStatus::Rented,
            'current_address' => 'Morada habitacional fictícia',
            'current_postal_code' => '0000-000',
            'current_city' => 'Localidade Demo',
            'current_parish' => 'Freguesia Demo',
            'current_municipality' => $municipality->name,
            'resides_in_municipality' => true,
            'residence_years_in_municipality' => 2,
            'works_in_municipality' => false,
            'current_housing_typology' => 'T1',
            'current_housing_rooms' => 1,
            'current_housing_condition' => HousingCondition::Adequate,
            'current_monthly_rent' => '350.00',
            'current_housing_expense' => '50.00',
            'is_overcrowded' => false,
            'is_at_risk_of_eviction' => false,
            'is_homeless' => false,
            'is_temporary_accommodation' => false,
            'is_domestic_violence_victim' => false,
            'has_accessibility_needs' => false,
            'has_high_rent_burden' => false,
            'request_reason' => 'Cenário fictício de processamento municipal.',
            'deleted_at' => null,
        ])->save();

        $application = Application::withTrashed()->firstOrNew([
            'application_number' => $applicationNumber,
        ]);
        if ($application->exists && (int) $application->contest_id !== (int) $contest->id) {
            throw new LogicException('Uma candidatura demo sintética pertence a outro concurso.');
        }
        $application->forceFill([
            'public_id' => $this->uuid('application:'.$applicationNumber),
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
            'status' => ApplicationStatus::Submitted,
            'candidate_notes' => 'Candidatura fictícia do cenário operacional.',
            'submitted_at' => $reference,
            'locked_at' => $reference,
            'declaration_accepted' => true,
            'declaration_accepted_at' => $reference,
            'contest_rules_accepted' => true,
            'contest_rules_accepted_at' => $reference,
            'data_processing_accepted' => true,
            'data_processing_accepted_at' => $reference,
            'truthfulness_accepted' => true,
            'truthfulness_accepted_at' => $reference,
            'data_current_confirmed' => true,
            'data_current_confirmed_at' => $reference,
            'created_by' => $candidate->id,
            'updated_by' => $candidate->id,
            'deleted_at' => null,
        ])->save();

        $process = AdministrativeProcess::withTrashed()->firstOrNew([
            'process_number' => $processNumber,
        ]);
        if ($process->exists && (int) $process->application_id !== (int) $application->id) {
            throw new LogicException('Um processo demo sintético pertence a outra candidatura.');
        }
        $process->forceFill([
            'application_id' => $application->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'user_id' => $candidate->id,
            'assigned_to' => $analyst->id,
            'status' => AdministrativeProcessStatus::EligibilityReview,
            'received_at' => $reference,
            'assigned_at' => $reference,
            'preliminary_review_started_at' => $reference,
            'document_review_started_at' => $reference,
            'eligibility_review_started_at' => $reference,
            'summary' => 'Processo fictício do cenário operacional do Programa 53.',
            'created_by' => $analyst->id,
            'updated_by' => $analyst->id,
            'deleted_at' => null,
        ])->save();

        return compact('application', 'process', 'candidate');
    }

    /**
     * @param  array<string, mixed>  $primary
     * @return array{
     *     application: Application,
     *     process: AdministrativeProcess,
     *     review: ApplicationReview,
     *     payload: array<string, mixed>
     * }
     */
    private function primaryInitialEntry(array $primary): array
    {
        $documents = DocumentSubmission::query()
            ->where('application_id', $primary['application']->id)
            ->with(['versions', 'currentVersion', 'requiredDocument'])
            ->orderBy('id')
            ->get();
        $target = $documents
            ->first(static fn (DocumentSubmission $document): bool => $document->versions->count() === 2);

        if (! $target instanceof DocumentSubmission) {
            throw new LogicException('O documento substituído demo não foi encontrado.');
        }

        $documentPayload = [];
        foreach ($documents as $document) {
            $firstVersion = $document->versions->sortBy('version_number')->first();

            $documentPayload[] = [
                'key' => 'document:'.$document->id,
                'id' => $document->id,
                'required_document_id' => $document->required_document_id,
                'document_type_id' => $document->document_type_id,
                'requirement_instance' => $document->requirement_instance,
                'reference_period' => $document->reference_period?->toDateString(),
                'status' => $document->is($target) ? 'rejected' : 'validated',
                'classification' => $document->is($target) ? 'invalid' : 'accepted',
                'checksum' => $document->is($target)
                    ? $firstVersion?->checksum
                    : $document->checksum,
                'current_version_id' => $document->is($target)
                    ? $firstVersion?->id
                    : $document->current_version_id,
                'target' => $this->snapshotDocumentTarget($document),
                'submitted_at' => $this->dateTime($document->submitted_at),
                'validated_at' => $document->is($target)
                    ? null
                    : $this->dateTime($document->validated_at),
            ];
        }

        $payload = $this->basePayload(
            $primary['application'],
            $primary['process'],
            ApplicationReviewBatchOutcome::CorrectionRequired,
            [
                'ready' => false,
                'total_required' => $documents->count(),
                'validated' => max(0, $documents->count() - 1),
                'submitted' => 0,
                'under_review' => 0,
                'missing' => 0,
                'rejected' => 1,
                'expired' => 0,
                'blockers' => ['Um documento exige substituição.'],
            ],
            $documentPayload,
            [[
                'key' => 'finding:document:'.$target->id,
                'finding_status' => 'invalid',
                'document_status' => 'rejected',
                'document_type_id' => $target->document_type_id,
                'required_document_id' => $target->required_document_id,
                'source_document_submission_id' => $target->id,
                'requirement_instance' => $target->requirement_instance,
                'title' => 'Substituição documental necessária',
                'description' => 'Documento fictício parcialmente ilegível.',
                'is_required' => true,
                'sort_order' => 1,
            ]],
        );
        $payload['review'] = [
            'id' => $primary['initial_review']->id,
            'type' => ApplicationReviewType::Documental->value,
            'status' => 'completed',
            'result' => ApplicationReviewResult::RequiresCorrection->value,
            'source_lock_version' => $primary['initial_review']->lock_version,
            'summary' => $primary['initial_review']->summary,
        ];

        return [
            'application' => $primary['application'],
            'process' => $primary['process'],
            'review' => $primary['initial_review'],
            'payload' => $payload,
        ];
    }

    /**
     * @param  array{application: Application, process: AdministrativeProcess}  $context
     * @return array{
     *     application: Application,
     *     process: AdministrativeProcess,
     *     review: null,
     *     payload: array<string, mixed>
     * }
     */
    private function noResponseEntry(array $context): array
    {
        $payload = $this->basePayload(
            $context['application'],
            $context['process'],
            ApplicationReviewBatchOutcome::CorrectionRequired,
            [
                'ready' => false,
                'total_required' => 1,
                'validated' => 0,
                'submitted' => 0,
                'under_review' => 0,
                'missing' => 1,
                'rejected' => 0,
                'expired' => 0,
                'blockers' => ['Documento obrigatório em falta.'],
            ],
            [],
            [[
                'key' => 'finding:missing:'.$context['application']->id,
                'finding_status' => 'missing',
                'document_status' => 'missing',
                'title' => 'Documento obrigatório em falta',
                'description' => 'Achado fictício para demonstrar ausência de resposta.',
                'is_required' => true,
                'sort_order' => 1,
            ]],
        );

        return [
            'application' => $context['application'],
            'process' => $context['process'],
            'review' => null,
            'payload' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $primary
     * @return array{
     *     application: Application,
     *     process: AdministrativeProcess,
     *     review: ApplicationReview,
     *     payload: array<string, mixed>
     * }
     */
    private function primaryRevalidationEntry(
        array $primary,
        CorrectionSubmissionReceipt $receipt,
    ): array {
        $response = $primary['correction_response']->refresh();
        $submission = $response->documentSubmission()
            ->with('versions')
            ->firstOrFail();
        $versions = $submission->versions->sortBy('version_number')->values();
        $first = $versions->get(0);
        $second = $versions->get(1);

        if ($first === null || $second === null) {
            throw new LogicException('A revalidação demo exige duas versões documentais.');
        }

        $decisionFingerprint = app(CanonicalJsonHasher::class)->hash([
            'correction_request_id' => $primary['correction_request']->id,
            'correction_response_id' => $response->id,
            'document_version_id' => $second->id,
            'checksum' => $second->checksum,
        ]);
        $response->forceFill([
            'response_kind' => CorrectionResponseKind::Document,
            'document_version_id' => $second->id,
            'prepared_at' => $response->prepared_at ?? $receipt->submitted_at,
            'submitted_at' => $response->submitted_at ?? $receipt->submitted_at,
            'review_result' => CorrectionResponseReviewResult::Accepted,
            'differential_classification' => CorrectionRevalidationItemType::ReplacedDocument,
            'decision_source_fingerprint' => $decisionFingerprint,
        ])->save();

        $documents = DocumentSubmission::query()
            ->where('application_id', $primary['application']->id)
            ->with(['versions', 'currentVersion', 'requiredDocument'])
            ->orderBy('id')
            ->get();
        $documentPayload = [];
        $carriedForwardItems = [];
        foreach ($documents as $document) {
            $replaced = $document->is($submission);
            $documentPayload[] = [
                'key' => 'document:'.$document->id,
                'id' => $document->id,
                'required_document_id' => $document->required_document_id,
                'document_type_id' => $document->document_type_id,
                'requirement_instance' => $document->requirement_instance,
                'reference_period' => $document->reference_period?->toDateString(),
                'status' => 'validated',
                'classification' => $replaced
                    ? CorrectionRevalidationItemType::ReplacedDocument->value
                    : CorrectionRevalidationItemType::UnchangedValid->value,
                'checksum' => $document->checksum,
                'submitted_checksum' => $document->checksum,
                'current_version_id' => $document->current_version_id,
                'target' => $this->snapshotDocumentTarget($document),
                'submitted_at' => $this->dateTime($document->submitted_at),
                'validated_at' => $this->dateTime($document->validated_at),
            ];
            if (! $replaced) {
                $carriedForwardItems[] = [
                    'key' => 'document:'.$document->id,
                    'required_document_id' => $document->required_document_id,
                    'document_type_id' => $document->document_type_id,
                    'requirement_instance' => $document->requirement_instance,
                    'reference_period' => $document->reference_period?->toDateString(),
                    'classification' => CorrectionRevalidationItemType::UnchangedValid->value,
                    'submitted_checksum' => $document->checksum,
                ];
            }
        }

        $payload = $this->basePayload(
            $primary['application'],
            $primary['process'],
            ApplicationReviewBatchOutcome::CompletePendingDecision,
            [
                'ready' => true,
                'carried_forward' => 14,
                'reviewed' => 1,
                'rejected' => 0,
                'blockers' => [],
            ],
            $documentPayload,
            [],
        );
        $payload += [
            'correction_request' => [
                'id' => $primary['correction_request']->id,
                'number' => $primary['correction_request']->request_number,
                'source_snapshot_hash' => $primary['correction_request']->source_snapshot_hash,
                'submitted_at' => $receipt->submitted_at->toIso8601String(),
            ],
            'submission_receipt' => [
                'id' => $receipt->id,
                'number' => $receipt->receipt_number,
                'snapshot_hash' => $receipt->snapshot_hash,
                'submitted_at' => $receipt->submitted_at->toIso8601String(),
            ],
            'carried_forward_items' => $carriedForwardItems,
            'changed_items' => [[
                'key' => 'document:'.$submission->id,
                'classification' => CorrectionRevalidationItemType::ReplacedDocument->value,
                'correction_response_id' => $response->id,
                'original_checksum' => $first->checksum,
                'submitted_checksum' => $second->checksum,
                'source_fingerprint' => $decisionFingerprint,
            ]],
            'justification_items' => [],
            'dependency_affected_items' => [],
            'decisions' => [[
                'key' => 'document:'.$submission->id,
                'correction_response_id' => $response->id,
                'classification' => CorrectionRevalidationItemType::ReplacedDocument->value,
                'result' => CorrectionResponseReviewResult::Accepted->value,
                'reviewed_by' => $primary['analyst']->id,
                'reviewed_at' => $response->reviewed_at?->toIso8601String(),
                'source_fingerprint' => $decisionFingerprint,
            ]],
            'aggregate_result' => [
                'value' => CorrectionRevalidationAggregateResult::Accepted->value,
                'label' => CorrectionRevalidationAggregateResult::Accepted->label(),
            ],
        ];
        $payload['review'] = [
            'id' => $primary['correction_review']->id,
            'type' => ApplicationReviewType::CorrectionResponse->value,
            'status' => 'completed',
            'result' => ApplicationReviewResult::Passed->value,
            'source_lock_version' => $primary['correction_review']->lock_version,
            'summary' => $primary['correction_review']->summary,
        ];

        return [
            'application' => $primary['application'],
            'process' => $primary['process'],
            'review' => $primary['correction_review'],
            'payload' => $payload,
        ];
    }

    /**
     * @return array{
     *     application: Application,
     *     process: AdministrativeProcess,
     *     review: null,
     *     payload: array<string, mixed>
     * }
     */
    private function completeEntry(
        Application $application,
        AdministrativeProcess $process,
    ): array {
        return [
            'application' => $application,
            'process' => $process,
            'review' => null,
            'payload' => $this->basePayload(
                $application,
                $process,
                ApplicationReviewBatchOutcome::CompletePendingDecision,
                [
                    'ready' => true,
                    'total_required' => 0,
                    'validated' => 0,
                    'submitted' => 0,
                    'under_review' => 0,
                    'missing' => 0,
                    'rejected' => 0,
                    'expired' => 0,
                    'blockers' => [],
                ],
                [],
                [],
            ),
        ];
    }

    /** @return array<string, int|null> */
    private function snapshotDocumentTarget(
        DocumentSubmission $document,
    ): array {
        $context = app(DocumentSubmissionContextResolver::class)
            ->resolve($document);

        return [
            $context['target_type'].'_id' => $context['target_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $readiness
     * @param  list<array<string, mixed>>  $documents
     * @param  list<array<string, mixed>>  $findings
     * @return array<string, mixed>
     */
    private function basePayload(
        Application $application,
        AdministrativeProcess $process,
        ApplicationReviewBatchOutcome $outcome,
        array $readiness,
        array $documents,
        array $findings,
    ): array {
        $technicalResult = match ($outcome) {
            ApplicationReviewBatchOutcome::CompletePendingDecision => ApplicationReviewResult::Passed->value,
            ApplicationReviewBatchOutcome::CorrectionRequired => ApplicationReviewResult::RequiresCorrection->value,
            ApplicationReviewBatchOutcome::CorrectionRejected => ApplicationReviewResult::Failed->value,
            ApplicationReviewBatchOutcome::Withdrawn,
            ApplicationReviewBatchOutcome::NotAssessed => ApplicationReviewResult::NotApplicable->value,
        };

        return [
            'schema_version' => 2,
            'process' => [
                'id' => $process->id,
                'number' => $process->process_number,
                'status' => $process->status->value,
                'assigned_to' => $process->assigned_to,
                'application_id' => $application->id,
                'contest_id' => $application->contest_id,
                'program_id' => $application->program_id,
            ],
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'number' => $application->application_number,
                'status' => $application->status->value,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'program_id' => $application->program_id,
                'contest_id' => $application->contest_id,
            ],
            'outcome' => $outcome->value,
            'technical_result' => $technicalResult,
            'review' => null,
            'readiness' => $readiness,
            'documents' => $documents,
            'findings' => $findings,
        ];
    }

    /**
     * @param  list<array{application: Application, process: AdministrativeProcess, review: ApplicationReview|null, payload: array<string, mixed>}>  $entries
     */
    private function ensureBatch(
        Municipality $municipality,
        Contest $contest,
        User $actor,
        ApplicationReviewBatchCycle $cycle,
        int $sequence,
        CarbonImmutable $sealedAt,
        string $reason,
        array $entries,
        ?CorrectionRequest $correctionRequest = null,
    ): ApplicationReviewBatch {
        $hasher = app(CanonicalJsonHasher::class);
        $prepared = [];

        foreach ($entries as $entry) {
            $snapshotHash = $hasher->hash($entry['payload']);
            $prepared[] = [
                ...$entry,
                'snapshot_hash' => $snapshotHash,
                'source_fingerprint' => $hasher->hash([
                    'demo' => true,
                    'cycle' => $cycle->value,
                    'application_id' => $entry['application']->id,
                    'snapshot_hash' => $snapshotHash,
                ]),
            ];
        }
        usort(
            $prepared,
            static fn (array $left, array $right): int => $left['application']->id <=> $right['application']->id,
        );
        $batchHash = $hasher->hash([
            'schema_version' => 1,
            'contest_id' => $contest->id,
            'cycle' => $cycle->value,
            'items' => array_map(
                static fn (array $entry): array => [
                    'application_id' => $entry['application']->id,
                    'snapshot_hash' => $entry['snapshot_hash'],
                    'payload' => $entry['payload'],
                ],
                $prepared,
            ),
        ]);
        $sealKey = hash('sha256', 'demo:program53:batch:'.$contest->id.':'.$cycle->value.':'.$sequence);
        $batch = ApplicationReviewBatch::query()->where('seal_key', $sealKey)->first();

        if ($batch instanceof ApplicationReviewBatch) {
            if (! hash_equals($batchHash, $batch->snapshot_hash) || $batch->items()->count() !== count($prepared)) {
                throw new LogicException('Um lote demo existente diverge do snapshot canónico.');
            }

            return $batch->load('items');
        }

        $batch = new ApplicationReviewBatch;
        $batch->forceFill([
            'public_id' => $this->uuid('batch:'.$sealKey),
            'municipality_id' => $municipality->id,
            'contest_id' => $contest->id,
            'correction_request_id' => $correctionRequest?->id,
            'cycle' => $cycle,
            'sequence_number' => $sequence,
            'status' => ApplicationReviewBatchStatus::Sealed,
            'reason' => $reason,
            'item_count' => count($prepared),
            'seal_key' => $sealKey,
            'source_fingerprint' => $hasher->hash([
                'demo' => true,
                'contest_id' => $contest->id,
                'cycle' => $cycle->value,
                'items' => array_column($prepared, 'source_fingerprint'),
            ]),
            'snapshot_hash' => $batchHash,
            'sealed_by' => $actor->id,
            'sealed_at' => $sealedAt,
            'created_at' => $sealedAt,
            'updated_at' => $sealedAt,
        ])->save();

        foreach ($prepared as $entry) {
            ApplicationReviewBatchItem::query()->create([
                'application_review_batch_id' => $batch->id,
                'administrative_process_id' => $entry['process']->id,
                'application_id' => $entry['application']->id,
                'application_review_id' => $entry['review']?->id,
                'process_number' => $entry['process']->process_number,
                'application_number' => $entry['application']->application_number,
                'application_public_id' => $entry['application']->public_id,
                'outcome' => $entry['payload']['outcome'],
                'technical_result' => $entry['payload']['technical_result'],
                'review_lock_version' => $entry['review']?->lock_version,
                'readiness_snapshot' => $entry['payload']['readiness'],
                'document_snapshot' => $entry['payload']['documents'],
                'snapshot_payload' => $entry['payload'],
                'source_fingerprint' => $entry['source_fingerprint'],
                'snapshot_hash' => $entry['snapshot_hash'],
                'created_at' => $sealedAt,
                'updated_at' => $sealedAt,
            ]);
        }

        $this->auditCreated($batch, $actor, 'demo_program53_batch_seeded');

        return $batch->refresh()->load('items');
    }

    private function ensurePublication(
        ApplicationReviewBatch $batch,
        User $actor,
        CarbonImmutable $publishedAt,
        string $reason,
    ): ApplicationReviewPublication {
        $existing = ApplicationReviewPublication::query()
            ->where('application_review_batch_id', $batch->id)
            ->first();
        if ($existing instanceof ApplicationReviewPublication) {
            if ($existing->results()->count() !== $batch->item_count) {
                throw new LogicException('Uma publicação demo está incompleta.');
            }

            return $existing->load('results');
        }

        $hasher = app(CanonicalJsonHasher::class);
        $prepared = $batch->items->map(function (ApplicationReviewBatchItem $item) use ($batch, $hasher): array {
            $payload = $this->candidateResultPayload($batch, $item);
            $resultHash = $hasher->hash($payload);

            return [
                'item' => $item,
                'payload' => $payload,
                'result_hash' => $resultHash,
                'notification_hash' => $hasher->hash([
                    'event_code' => OfficialNotificationType::ApplicationReviewResultPublished->value,
                    'result_hash' => $resultHash,
                ]),
            ];
        })->values();
        $publicationHash = $hasher->hash([
            'schema_version' => 1,
            'batch_id' => $batch->id,
            'batch_public_id' => $batch->public_id,
            'contest_id' => $batch->contest_id,
            'cycle' => $batch->cycle->value,
            'sequence_number' => $batch->sequence_number,
            'reason' => $reason,
            'source_snapshot_hash' => $batch->snapshot_hash,
            'results' => $prepared->map(static fn (array $entry): array => [
                'batch_item_id' => $entry['item']->id,
                'result_hash' => $entry['result_hash'],
                'notification_hash' => $entry['notification_hash'],
            ])->all(),
        ]);
        $publicationKey = hash('sha256', 'demo:program53:publication:'.$batch->id);
        $publication = new ApplicationReviewPublication;
        $publication->forceFill([
            'public_id' => $this->uuid('publication:'.$publicationKey),
            'municipality_id' => $batch->municipality_id,
            'contest_id' => $batch->contest_id,
            'application_review_batch_id' => $batch->id,
            'cycle' => $batch->cycle,
            'sequence_number' => $batch->sequence_number,
            'status' => ApplicationReviewPublicationStatus::Published,
            'reason' => $reason,
            'item_count' => $batch->item_count,
            'publication_key' => $publicationKey,
            'source_snapshot_hash' => $batch->snapshot_hash,
            'publication_hash' => $publicationHash,
            'published_by' => $actor->id,
            'published_at' => $publishedAt,
            'created_at' => $publishedAt,
            'updated_at' => $publishedAt,
        ])->save();

        foreach ($prepared as $entry) {
            $item = $entry['item'];
            $application = Application::query()->findOrFail($item->application_id);
            $candidate = User::query()->findOrFail($application->user_id);
            $resultPublicId = $this->uuid('publication-result:'.$publication->id.':'.$item->id);
            $notification = app(OfficialNotificationService::class)->createInternal(
                user: $candidate,
                type: OfficialNotificationType::ApplicationReviewResultPublished,
                subject: 'Resultado da revisão documental disponível',
                body: (string) $entry['payload']['message'],
                notifiable: $publication,
                application: $application,
                actor: $actor,
                requiresAcknowledgement: false,
                actionUrl: route(
                    'candidate.application-review-results.show',
                    ['reviewResult' => $resultPublicId],
                    false,
                ),
            );
            $communication = $notification->communication()->firstOrFail();
            $inApp = $communication->deliveries()
                ->where('channel', CommunicationChannel::InApp->value)
                ->sole();
            $email = $communication->deliveries()
                ->where('channel', CommunicationChannel::Email->value)
                ->sole();

            ApplicationReviewPublicationResult::query()->create([
                'public_id' => $resultPublicId,
                'application_review_publication_id' => $publication->id,
                'application_review_batch_item_id' => $item->id,
                'municipality_id' => $publication->municipality_id,
                'contest_id' => $publication->contest_id,
                'administrative_process_id' => $item->administrative_process_id,
                'application_id' => $item->application_id,
                'user_id' => $candidate->id,
                'process_number' => $item->process_number,
                'application_number' => $item->application_number,
                'application_public_id' => $item->application_public_id,
                'outcome' => $item->outcome,
                'technical_result' => $item->technical_result,
                'result_payload' => $entry['payload'],
                'source_snapshot_hash' => $item->snapshot_hash,
                'result_hash' => $entry['result_hash'],
                'notification_hash' => $entry['notification_hash'],
                'official_notification_id' => $notification->id,
                'communication_log_id' => $communication->id,
                'in_app_delivery_id' => $inApp->id,
                'email_delivery_id' => $email->id,
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);
        }

        $this->auditCreated($publication, $actor, 'demo_program53_publication_seeded');

        return $publication->refresh()->load('results');
    }

    /** @return array<string, mixed> */
    private function candidateResultPayload(
        ApplicationReviewBatch $batch,
        ApplicationReviewBatchItem $item,
    ): array {
        [$message, $nextAction] = match ($item->outcome) {
            ApplicationReviewBatchOutcome::CompletePendingDecision => [
                'A revisão documental terminou sem bloqueios. O resultado não constitui decisão administrativa.',
                'await_formal_decision',
            ],
            ApplicationReviewBatchOutcome::CorrectionRequired => [
                'A revisão identificou elementos que necessitam de aperfeiçoamento.',
                'await_correction_request',
            ],
            ApplicationReviewBatchOutcome::CorrectionRejected => [
                'A segunda análise identificou elementos não aceites, sem exclusão automática.',
                'await_formal_decision',
            ],
            ApplicationReviewBatchOutcome::Withdrawn => [
                'A desistência encontra-se registada neste ciclo.',
                'none',
            ],
            ApplicationReviewBatchOutcome::NotAssessed => [
                'A candidatura não foi avaliada neste ciclo.',
                'await_municipal_information',
            ],
        };

        return [
            'schema_version' => 1,
            'cycle' => $batch->cycle->value,
            'cycle_label' => $batch->cycle->label(),
            'process_number' => $item->process_number,
            'application_number' => $item->application_number,
            'application_public_id' => $item->application_public_id,
            'outcome' => $item->outcome->value,
            'outcome_label' => $item->outcome->label(),
            'technical_result' => $item->technical_result,
            'message' => $message,
            'next_action' => $nextAction,
            'source_snapshot_hash' => $item->snapshot_hash,
        ];
    }

    /** @param array<string, mixed> $primary */
    private function alignPrimaryCorrection(
        array $primary,
        ApplicationReviewPublicationResult $result,
        CarbonImmutable $reference,
    ): void {
        $request = $primary['correction_request'];
        $request->forceFill([
            'application_review_publication_result_id' => $result->id,
            'source_snapshot_hash' => $result->source_snapshot_hash,
            'issued_at' => $reference->subDays(16)->setTime(10, 5),
            'notified_at' => $reference->subDays(16)->setTime(10, 5),
            'opened_at' => $reference->subDays(16)->setTime(10, 5),
            'response_deadline_at' => $reference->subDays(8)->setTime(17, 0),
            'original_response_deadline_at' => $reference->subDays(8)->setTime(17, 0),
            'responded_at' => $reference->subDays(9)->setTime(15, 30),
            'submitted_at' => $reference->subDays(9)->setTime(15, 30),
            'resolved_at' => $reference->subDays(5)->setTime(10, 30),
        ])->save();
    }

    /** @param array<string, mixed> $primary */
    private function ensureSubmissionReceipt(
        array $primary,
        CarbonImmutable $submittedAt,
    ): CorrectionSubmissionReceipt {
        $request = $primary['correction_request']->refresh()->load(['items', 'responses']);
        $existing = CorrectionSubmissionReceipt::query()
            ->where('correction_request_id', $request->id)
            ->first();
        if ($existing instanceof CorrectionSubmissionReceipt) {
            return $existing;
        }

        $response = $primary['correction_response']->refresh();
        $version = $response->documentSubmission?->currentVersion;
        if ($version === null) {
            throw new LogicException('A resposta demo não possui versão documental atual.');
        }
        $response->forceFill([
            'response_kind' => CorrectionResponseKind::Document,
            'document_version_id' => $version->id,
            'prepared_at' => $submittedAt->subMinutes(30),
            'submitted_at' => $submittedAt,
        ])->save();

        $receiptNumber = 'REC-'.$request->request_number;
        $snapshot = [
            'schema_version' => 1,
            'receipt_number' => $receiptNumber,
            'request' => [
                'id' => $request->id,
                'number' => $request->request_number,
                'source_snapshot_hash' => $request->source_snapshot_hash,
            ],
            'application' => [
                'id' => $primary['application']->id,
                'public_id' => $primary['application']->public_id,
            ],
            'items' => $request->items->map(static fn ($item): array => [
                'id' => $item->id,
                'status' => $item->status->value,
                'required' => (bool) $item->is_required,
            ])->all(),
            'responses' => [[
                'id' => $response->id,
                'document_submission_id' => $response->document_submission_id,
                'document_version_id' => $version->id,
                'checksum' => $version->checksum,
            ]],
            'submitted_at' => $submittedAt->toIso8601String(),
        ];
        $notification = app(OfficialNotificationService::class)->createInternal(
            user: $primary['analyst'],
            type: OfficialNotificationType::CorrectionSubmissionReceived,
            subject: 'Aperfeiçoamento submetido — cenário demo',
            body: 'O aperfeiçoamento fictício foi formalmente submetido.',
            notifiable: $request,
            application: $primary['application'],
            actor: $primary['candidate'],
            channel: OfficialNotificationChannel::Backoffice,
            requiresAcknowledgement: false,
            actionUrl: route('backoffice.correction-requests.show', $request, false),
            enforceMandatoryEmail: false,
        );
        $receipt = new CorrectionSubmissionReceipt;
        $receipt->forceFill([
            'correction_request_id' => $request->id,
            'application_id' => $primary['application']->id,
            'user_id' => $primary['candidate']->id,
            'municipal_notification_id' => $notification->id,
            'receipt_number' => $receiptNumber,
            'snapshot_payload' => $snapshot,
            'snapshot_hash' => app(CanonicalJsonHasher::class)->hash($snapshot),
            'submitted_at' => $submittedAt,
            'created_at' => $submittedAt,
        ])->save();
        $this->auditCreated($receipt, $primary['candidate'], 'demo_program53_receipt_seeded');

        return $receipt->refresh();
    }

    /**
     * @param  array{application: Application, process: AdministrativeProcess, candidate: User}  $context
     */
    private function ensureNoResponseCorrection(
        array $context,
        ApplicationReviewPublicationResult $result,
        User $analyst,
        CarbonImmutable $reference,
    ): CorrectionRequest {
        $request = CorrectionRequest::withTrashed()
            ->where('application_review_publication_result_id', $result->id)
            ->first();
        if (! $request instanceof CorrectionRequest) {
            $request = new CorrectionRequest([
                'subject' => 'Pedido fictício sem resposta do candidato',
                'message' => 'O cenário demonstra um pedido expirado sem resposta.',
                'legal_basis' => 'Dados exclusivamente fictícios e sem efeitos administrativos.',
                'instructions' => 'Não responder: cenário de controlo operacional.',
                'candidate_visible' => true,
            ]);
            $request->forceFill([
                'application_review_publication_result_id' => $result->id,
                'source_snapshot_hash' => $result->source_snapshot_hash,
                'administrative_process_id' => $context['process']->id,
                'application_id' => $context['application']->id,
                'user_id' => $context['candidate']->id,
                'request_number' => 'APR-DEMO-SEM-RESPOSTA-2026',
                'status' => CorrectionRequestStatus::Expired,
                'issued_by' => $analyst->id,
                'issued_at' => $reference->subDays(16),
                'notified_at' => $reference->subDays(16),
                'opened_at' => $reference->subDays(16),
                'response_deadline_at' => $reference->subDays(8)->setTime(17, 0),
                'original_response_deadline_at' => $reference->subDays(8)->setTime(17, 0),
                'deadline_extension_count' => 0,
                'expired_at' => $reference->subDays(7)->setTime(9, 0),
                'created_at' => $reference->subDays(16),
                'updated_at' => $reference->subDays(7),
            ])->save();
            $item = $request->items()->make([
                'issue_type' => CorrectionIssueType::MissingData,
                'title' => 'Elemento obrigatório em falta',
                'description' => 'Achado fictício sem conteúdo pessoal.',
                'required_action' => CorrectionRequiredAction::ConfirmInformation,
                'is_required' => true,
                'sort_order' => 1,
            ]);
            $item->forceFill([
                'status' => CorrectionRequestItemStatus::Pending,
            ])->save();
            $this->auditCreated($request, $analyst, 'demo_program53_no_response_seeded');
        }

        $context['process']->forceFill([
            'current_correction_request_id' => $request->id,
            'status' => AdministrativeProcessStatus::CorrectionOverdue,
            'updated_by' => $analyst->id,
        ])->save();

        return $request->refresh();
    }

    /** @param array<string, mixed> $primary */
    private function alignRevalidationProjection(
        array $primary,
        ApplicationReviewPublicationResult $result,
        CarbonImmutable $reference,
    ): void {
        $primary['correction_request']->forceFill([
            'revalidation_started_by' => $primary['analyst']->id,
            'revalidation_started_at' => $reference->subDays(7)->setTime(9, 0),
            'revalidation_result' => CorrectionRevalidationAggregateResult::Accepted,
            'revalidation_publication_result_id' => $result->id,
            'revalidation_projected_by' => $primary['analyst']->id,
            'revalidation_projected_at' => $reference->subDays(5)->setTime(10, 30),
        ])->save();
    }

    /**
     * @return array{municipality: Municipality, analyst: User, contest: Contest, application: Application, process: AdministrativeProcess}
     */
    private function ensureControlMunicipality(
        MunicipalApplicationDemoContext $context,
        CarbonImmutable $reference,
    ): array {
        $municipality = Municipality::query()->firstOrNew([
            'code' => self::CONTROL_MUNICIPALITY_CODE,
        ]);
        $municipality->forceFill([
            'name' => 'Município de Controlo — Demonstração MV-HAB',
            'tax_number' => null,
            'contact_email' => 'isolamento@demo.mvhab.test',
            'settings' => [
                'demo' => true,
                'demo_only' => true,
                'administrative_effects' => false,
                'purpose' => 'negative_cross_municipality_control',
            ],
            'active' => true,
        ])->save();

        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas');
        $role = Role::query()->firstOrNew(['name' => self::CONTROL_ROLE_NAME]);
        if ($role->exists && (int) $role->municipality_id !== (int) $municipality->id) {
            throw new LogicException('A role de controlo pertence a outro Município.');
        }
        $role->forceFill([
            'municipality_id' => $municipality->id,
            'template_key' => $template['key'],
            'template_version' => $template['version'],
            'template_fingerprint' => $template['fingerprint'],
            'label' => 'Analista de controlo de isolamento',
            'description' => 'Role fictícia para comprovar isolamento municipal.',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ])->save();
        $role->permissions()->sync($template['permission_ids']);

        $analyst = $this->upsertControlUser(
            self::CONTROL_ANALYST_EMAIL,
            'Analista de Isolamento Demo',
            $municipality,
            $context->userPassword(),
            $reference,
            $role,
            true,
        );
        $candidateRole = Role::query()
            ->where('name', 'candidate')
            ->where('is_system', true)
            ->sole();
        $candidate = $this->upsertControlUser(
            self::CONTROL_CANDIDATE_EMAIL,
            'Candidato de Isolamento Demo',
            $municipality,
            $context->userPassword(),
            $reference,
            $candidateRole,
            false,
        );
        $entitlements = app(MunicipalityEntitlementService::class);
        foreach ([FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview] as $feature) {
            $entitlements->enableFor(
                $municipality,
                $feature,
                $analyst,
                'Ativação fictícia para validar isolamento municipal do Programa 53.',
            );
        }

        $program = Program::withTrashed()->firstOrNew([
            'slug' => 'programa-demo-isolamento-programa-53',
        ]);
        $program->forceFill([
            'municipality_id' => $municipality->id,
            'created_by' => $analyst->id,
            'updated_by' => $analyst->id,
            'name' => 'Programa de Controlo de Isolamento — Demo',
            'summary' => 'Programa fictício do Município de controlo.',
            'description' => 'Dados sintéticos e sem efeitos administrativos.',
            'legal_basis' => 'Cenário técnico de demonstração.',
            'status' => ProgramStatus::Published,
            'starts_at' => $reference->subMonth(),
            'ends_at' => null,
            'published_at' => $reference->subMonth(),
            'deleted_at' => null,
        ])->save();
        $contest = Contest::withTrashed()->firstOrNew([
            'code' => self::CONTROL_CONTEST_CODE,
        ]);
        $contest->forceFill([
            'program_id' => $program->id,
            'created_by' => $analyst->id,
            'updated_by' => $analyst->id,
            'slug' => 'concurso-demo-isolamento-2026',
            'title' => 'Concurso de Controlo de Isolamento — Demo',
            'summary' => 'Concurso fictício sem publicação externa.',
            'description' => 'Cenário técnico isolado.',
            'application_instructions' => 'Sem efeitos administrativos.',
            'status' => ContestStatus::Published,
            'opens_at' => $reference->subDays(20),
            'closes_at' => $reference->subDays(10),
            'published_at' => $reference->subDays(21),
            'deleted_at' => null,
        ])->save();
        $applicationContext = $this->ensureSyntheticApplication(
            municipality: $municipality,
            program: $program,
            contest: $contest,
            analyst: $analyst,
            candidateEmail: $candidate->email,
            candidateName: $candidate->name,
            applicationNumber: 'CAND-DEMO-ISOLAMENTO-2026',
            processNumber: 'PROC-DEMO-ISOLAMENTO-2026',
            reference: $reference->subDays(12),
        );

        return [
            'municipality' => $municipality->refresh(),
            'analyst' => $analyst->refresh(),
            'contest' => $contest->refresh(),
            'application' => $applicationContext['application'],
            'process' => $applicationContext['process'],
        ];
    }

    private function upsertControlUser(
        string $email,
        string $name,
        Municipality $municipality,
        string $password,
        CarbonImmutable $reference,
        Role $role,
        bool $mfaRequired,
    ): User {
        $user = User::query()->firstOrNew(['email' => $email]);
        if ($user->exists && (int) $user->municipality_id !== (int) $municipality->id) {
            throw new LogicException('Um utilizador de controlo pertence a outro Município.');
        }
        $attributes = [
            'municipality_id' => $municipality->id,
            'name' => $name,
            'status' => 'active',
            'email_verified_at' => $reference,
            'mfa_required' => $mfaRequired,
            'internal_notes' => 'Conta fictícia de controlo de isolamento.',
        ];
        if (! $user->exists || ! Hash::check($password, (string) $user->getAuthPassword())) {
            $attributes['password'] = $password;
        }
        $user->forceFill($attributes)->save();
        $user->roles()->sync([$role->id]);

        return $user->refresh();
    }

    /** @param array<string, mixed> $primary */
    private function ensureTemporalExports(
        array $primary,
        ApplicationReviewBatch $initialBatch,
        ApplicationReviewBatch $revalidationBatch,
        CarbonImmutable $reference,
    ): void {
        $definition = ReportDefinition::query()
            ->where('code', TemporalApplicationResultExportService::REPORT_CODE)
            ->sole();
        $actor = User::query()
            ->where('email', MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL)
            ->where('municipality_id', $primary['municipality']->id)
            ->sole();
        $common = [
            'formats' => array_map(
                static fn (ApplicationResultExportFormat $format): string => $format->value,
                ApplicationResultExportFormat::cases(),
            ),
            'datasets' => array_map(
                static fn (ApplicationResultExportDataset $dataset): string => $dataset->value,
                ApplicationResultExportDataset::cases(),
            ),
            'csv_delimiter' => 'semicolon',
            'csv_bom' => true,
            'include_sensitive' => false,
            'include_document_files' => false,
            'changed_documents_only' => false,
            'include_unchanged' => true,
        ];
        $definitions = [
            'sealed' => [
                ...$common,
                'datasets' => [
                    ApplicationResultExportDataset::Applications->value,
                    ApplicationResultExportDataset::Documents->value,
                    ApplicationResultExportDataset::Findings->value,
                ],
                'mode' => ApplicationResultExportMode::SealedBatch,
                'parameters' => ['batch_public_id' => $initialBatch->public_id],
            ],
            'delta' => [
                ...$common,
                'mode' => ApplicationResultExportMode::DeltaBetweenBatches,
                'parameters' => [
                    'base_batch_public_id' => $initialBatch->public_id,
                    'target_batch_public_id' => $revalidationBatch->public_id,
                ],
            ],
        ];

        foreach ($definitions as $key => $data) {
            $idempotencyKey = hash('sha256', self::TEMPORAL_EXPORT_TOKENS[$key]);
            $export = ReportExport::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if (! $export instanceof ReportExport) {
                $requestedAt = $reference->subDays(2)->setTime(9, $key === 'sealed' ? 0 : 30);
                $run = new ReportRun;
                $run->forceFill([
                    'public_id' => $this->uuid('report-run:'.$key),
                    'report_definition_id' => $definition->id,
                    'user_id' => $actor->id,
                    'status' => ReportRunStatus::Started,
                    'format' => ReportFormat::Zip,
                    'scope' => ExportScope::Pseudonymized,
                    'filters' => [
                        'contest_id' => $primary['contest']->id,
                        'mode' => $data['mode']->value,
                        'source_parameters' => $data['parameters'],
                        'formats' => $data['formats'],
                        'datasets' => $data['datasets'],
                    ],
                    'started_at' => $requestedAt,
                    'created_at' => $requestedAt,
                    'updated_at' => $requestedAt,
                ])->save();
                $export = new ReportExport;
                $export->forceFill([
                    'public_id' => $this->uuid('report-export:'.$key),
                    'report_run_id' => $run->id,
                    'user_id' => $actor->id,
                    'municipality_id' => $primary['municipality']->id,
                    'contest_id' => $primary['contest']->id,
                    'export_profile' => TemporalApplicationResultExportService::PROFILE,
                    'export_mode' => $data['mode'],
                    'status' => ReportExportStatus::Pending,
                    'requested_format' => ReportFormat::Zip,
                    'format' => ReportFormat::Zip,
                    'scope' => ExportScope::Pseudonymized,
                    'disk' => 'local',
                    'file_path' => '',
                    'file_name' => '',
                    'processing_stage' => ApplicationResultExportStage::Queued,
                    'progress' => 0,
                    'expires_at' => $reference->addDays(7),
                    'source_metadata' => [
                        'operational' => [
                            'operation_id' => 'demo-program53-export-'.$key,
                            'attempt' => 0,
                        ],
                        'parameters' => $data['parameters'],
                        'request_options' => [
                            'csv_delimiter' => ';',
                            'csv_bom' => true,
                            'include_unchanged' => true,
                            'changed_documents_only' => false,
                            'sensitive_confirmed' => false,
                            'document_files_confirmed' => false,
                        ],
                    ],
                    'idempotency_key' => $idempotencyKey,
                    'formats' => $data['formats'],
                    'datasets' => $data['datasets'],
                    'sensitive_fields_included' => false,
                    'document_files_requested' => false,
                    'document_files_included' => false,
                    'created_at' => $requestedAt,
                    'updated_at' => $requestedAt,
                ])->save();
            }

            if ($export->status !== ReportExportStatus::Completed) {
                app(TemporalApplicationResultExportService::class)
                    ->process((int) $export->id);
                $export->refresh();
            }
            if (
                $export->status !== ReportExportStatus::Completed
                || trim($export->file_path) === ''
                || ! Storage::disk('local')->exists($export->file_path)
            ) {
                throw new LogicException('Uma exportação temporal demo não foi concluída.');
            }
        }
    }

    private function dateTime(mixed $value): ?string
    {
        return $value instanceof CarbonInterface
            ? $value->toIso8601String()
            : null;
    }

    private function auditCreated(
        Model $model,
        User $actor,
        string $action,
    ): void {
        app(AuditLogger::class)->record(
            AuditEvents::CREATE,
            $model,
            'applications',
            $action,
            'Artefacto fictício do Programa 53 criado pelo seeder demo.',
            metadata: [
                'actor_id' => $actor->id,
                'demo' => true,
                'demo_only' => true,
                'administrative_effects' => false,
            ],
        );
    }

    private function uuid(string $key): string
    {
        return Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            'https://demo.mvhab.test/program53/'.$key,
        )->toString();
    }
}
