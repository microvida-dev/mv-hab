<?php

namespace App\Services\Demo;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReportStatus;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseStatus;
use App\Enums\DocumentDossierStatus;
use App\Enums\DocumentStatus;
use App\Enums\FeatureKey;
use App\Enums\ReportExportStatus;
use App\Enums\VisitStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewPublication;
use App\Models\Contest;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\CorrectionSubmissionReceipt;
use App\Models\DocumentDossier;
use App\Models\HousingVisit;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\PlatformOperatorAssignment;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use App\Services\Support\CanonicalJsonHasher;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCatalogSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoProgram53Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

final class MunicipalApplicationDemoSummaryService
{
    /**
     * @var array<string, string>
     */
    private const ACCOUNT_ROLES = [
        MunicipalApplicationDemoAccessSeeder::OPERATOR_EMAIL => MunicipalApplicationDemoAccessSeeder::OPERATOR_ROLE_NAME,
        MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL => MunicipalApplicationDemoAccessSeeder::ANALYST_ROLE_NAME,
        MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_EMAIL => MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_ROLE_NAME,
        MunicipalApplicationDemoAccessSeeder::EXPORTER_EMAIL => MunicipalApplicationDemoAccessSeeder::EXPORTER_ROLE_NAME,
        MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL => MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_ROLE_NAME,
        MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL => 'candidate',
    ];

    /**
     * @return array<string, mixed>
     */
    public function verify(): array
    {
        $municipality = Municipality::query()
            ->where(
                'code',
                MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
            )
            ->sole();

        $contest = Contest::query()
            ->where(
                'code',
                MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
            )
            ->sole();

        $candidate = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            )
            ->sole();

        $application = Application::query()
            ->where('user_id', $candidate->id)
            ->where('contest_id', $contest->id)
            ->sole();

        $process = AdministrativeProcess::query()
            ->where('application_id', $application->id)
            ->sole();

        $correctionRequest = CorrectionRequest::query()
            ->where('application_id', $application->id)
            ->sole();

        $correctionResponse = CorrectionResponse::query()
            ->where('application_id', $application->id)
            ->sole();

        $visit = HousingVisit::query()
            ->where('application_id', $application->id)
            ->sole();

        $reports = ApplicationReport::query()
            ->where('application_id', $application->id)
            ->orderBy('format')
            ->get();

        $dossier = DocumentDossier::query()
            ->where('application_id', $application->id)
            ->sole();

        $counts = $this->counts(
            municipalityId: (int) $municipality->id,
            contestId: (int) $contest->id,
            applicationId: (int) $application->id,
            dossierId: (int) $dossier->id,
        );
        $program53 = $this->program53Scenario(
            municipality: $municipality,
            contest: $contest,
            application: $application,
        );
        $counts = [...$counts, ...$program53['counts']];

        $expectedCounts = [
            'municipal_roles' => 5,
            'demo_users' => 6,
            'housing_units' => 3,
            'contest_housing_units' => 3,
            'applications' => 1,
            'housing_preferences' => 3,
            'application_snapshots' => 8,
            'application_documents' => 15,
            'document_submissions' => 15,
            'document_versions' => 16,
            'administrative_processes' => 1,
            'application_reviews' => 2,
            'correction_requests' => 1,
            'correction_responses' => 1,
            'visit_availabilities' => 1,
            'visit_slots' => 4,
            'housing_visits' => 1,
            'application_reports' => 2,
            'document_dossiers' => 1,
            'document_dossier_items' => 15,
            'official_notifications' => 9,
            'candidate_interactions' => 3,
            'work_tasks' => 1,
            'document_ai_analyses' => 0,
            'municipalities' => 2,
            'contest_applications' => 2,
            'review_batches' => 2,
            'review_batch_items' => 3,
            'review_publications' => 2,
            'review_publication_results' => 3,
            'correction_submission_receipts' => 1,
            'expired_without_response' => 1,
            'temporal_exports' => 2,
            'control_applications' => 1,
            'control_review_batches' => 1,
            'control_publications' => 1,
        ];

        $this->assertCounts($counts, $expectedCounts);

        $this->assertRawStatus(
            $application,
            'status',
            ApplicationStatus::Submitted->value,
            'A candidatura demo não está submetida.',
        );
        $this->assertRawStatus(
            $process,
            'status',
            AdministrativeProcessStatus::EligibilityReview->value,
            'O processo demo não terminou em análise de requisitos.',
        );
        $this->assertRawStatus(
            $correctionRequest,
            'status',
            CorrectionRequestStatus::Resolved->value,
            'O pedido de aperfeiçoamento demo não está aceite.',
        );
        $this->assertRawStatus(
            $correctionResponse,
            'status',
            CorrectionResponseStatus::Accepted->value,
            'A resposta ao aperfeiçoamento demo não está aceite.',
        );
        $this->assertRawStatus(
            $visit,
            'status',
            VisitStatus::Completed->value,
            'A visita demo não está concluída.',
        );
        $this->assertRawStatus(
            $dossier,
            'status',
            DocumentDossierStatus::Standardized->value,
            'O dossier demo não está padronizado.',
        );

        if (
            DB::table('document_submissions')
                ->where('application_id', $application->id)
                ->where(
                    'status',
                    '!=',
                    DocumentStatus::Validated->value,
                )
                ->exists()
        ) {
            throw new LogicException(
                'Existem documentos demo por validar.',
            );
        }

        if (
            $reports->count() !== 2
            || $reports->contains(
                static fn (
                    ApplicationReport $report,
                ): bool => (string) $report->getRawOriginal('status')
                    !== ApplicationReportStatus::Generated->value,
            )
        ) {
            throw new LogicException(
                'Os relatórios municipais demo não estão gerados.',
            );
        }

        $reportFormats = $reports
            ->map(
                static fn (
                    ApplicationReport $report,
                ): string => (string) $report->getRawOriginal('format'),
            )
            ->sort()
            ->values()
            ->all();

        if ($reportFormats !== ['csv', 'html']) {
            throw new LogicException(
                'Os formatos de relatório demo não correspondem '
                .'a CSV e HTML.',
            );
        }

        $reportPaths = [];

        foreach ($reports as $report) {
            $path = $report->getAttribute('file_path');

            if (
                ! is_string($path)
                || ! Storage::disk('local')->exists($path)
            ) {
                throw new LogicException(
                    'Um ficheiro de relatório demo não está disponível.',
                );
            }

            $reportPaths[] = $path;
        }

        $dossierPath = $dossier->getAttribute('file_path');

        if (
            ! is_string($dossierPath)
            || ! Storage::disk('local')->exists($dossierPath)
        ) {
            throw new LogicException(
                'O ficheiro do dossier demo não está disponível.',
            );
        }

        $accounts = $this->accounts(
            municipalityId: (int) $municipality->id,
        );
        $program53Profile = $this->program53Profile($municipality);

        return [
            'demo_notice' => 'Dados fictícios e sem efeitos administrativos.',
            'municipality' => [
                'id' => (int) $municipality->id,
                'code' => (string) $municipality->code,
                'name' => (string) $municipality->name,
            ],
            'contest' => [
                'id' => (int) $contest->id,
                'code' => (string) $contest->code,
                'title' => (string) $contest->title,
            ],
            'application' => [
                'id' => (int) $application->id,
                'number' => (string) $application->application_number,
                'status' => (string) $application->getRawOriginal(
                    'status',
                ),
                'process_number' => (string) $process->process_number,
                'process_status' => (string) $process->getRawOriginal(
                    'status',
                ),
            ],
            'counts' => $counts,
            'accounts' => $accounts,
            'program53_profile' => $program53Profile,
            'program53' => $program53,
            'files' => [
                'reports' => $reportPaths,
                'document_dossier' => $dossierPath,
                'temporal_exports' => $program53['files'],
            ],
            'verified_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function counts(
        int $municipalityId,
        int $contestId,
        int $applicationId,
        int $dossierId,
    ): array {
        $submissionIds = DB::table('document_submissions')
            ->where('application_id', $applicationId)
            ->select('id');

        $availabilityIds = DB::table('visit_availabilities')
            ->where('contest_id', $contestId)
            ->select('id');

        return [
            'municipal_roles' => Role::query()
                ->where('municipality_id', $municipalityId)
                ->count(),
            'demo_users' => User::query()
                ->where('municipality_id', $municipalityId)
                ->whereIn('email', array_keys(self::ACCOUNT_ROLES))
                ->count(),
            'housing_units' => DB::table('housing_units')
                ->where('municipality_id', $municipalityId)
                ->count(),
            'contest_housing_units' => DB::table(
                'contest_housing_units',
            )
                ->where('contest_id', $contestId)
                ->count(),
            'applications' => DB::table('applications')
                ->where('id', $applicationId)
                ->count(),
            'housing_preferences' => DB::table('housing_preferences')
                ->where('application_id', $applicationId)
                ->count(),
            'application_snapshots' => DB::table(
                'application_snapshots',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'application_documents' => DB::table(
                'application_documents',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'document_submissions' => DB::table(
                'document_submissions',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'document_versions' => DB::table('document_versions')
                ->whereIn('document_submission_id', $submissionIds)
                ->count(),
            'administrative_processes' => DB::table(
                'administrative_processes',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'application_reviews' => DB::table(
                'application_reviews',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'correction_requests' => DB::table(
                'correction_requests',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'correction_responses' => DB::table(
                'correction_responses',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'visit_availabilities' => DB::table(
                'visit_availabilities',
            )
                ->where('contest_id', $contestId)
                ->count(),
            'visit_slots' => DB::table('visit_slots')
                ->whereIn(
                    'visit_availability_id',
                    $availabilityIds,
                )
                ->count(),
            'housing_visits' => DB::table('housing_visits')
                ->where('application_id', $applicationId)
                ->count(),
            'application_reports' => DB::table(
                'application_reports',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'document_dossiers' => DB::table('document_dossiers')
                ->where('application_id', $applicationId)
                ->count(),
            'document_dossier_items' => DB::table(
                'document_dossier_items',
            )
                ->where('document_dossier_id', $dossierId)
                ->count(),
            'official_notifications' => DB::table(
                'official_notifications',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'candidate_interactions' => DB::table(
                'candidate_interactions',
            )
                ->where('application_id', $applicationId)
                ->count(),
            'work_tasks' => DB::table('work_tasks')
                ->where(
                    'source',
                    'like',
                    'housing_visit:%',
                )
                ->count(),
            'document_ai_analyses' => DB::table(
                'document_ai_analyses',
            )
                ->whereIn('document_submission_id', $submissionIds)
                ->count(),
        ];
    }

    /**
     * @return array{
     *     counts: array<string, int>,
     *     municipality_isolation: array<string, int|bool>,
     *     exports: list<array<string, mixed>>,
     *     files: list<string>
     * }
     */
    private function program53Scenario(
        Municipality $municipality,
        Contest $contest,
        Application $application,
    ): array {
        $controlMunicipality = Municipality::query()
            ->where(
                'code',
                MunicipalApplicationDemoProgram53Seeder::CONTROL_MUNICIPALITY_CODE,
            )
            ->sole();
        $controlContest = Contest::query()
            ->where(
                'code',
                MunicipalApplicationDemoProgram53Seeder::CONTROL_CONTEST_CODE,
            )
            ->sole();
        $controlAnalyst = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoProgram53Seeder::CONTROL_ANALYST_EMAIL,
            )
            ->where('municipality_id', $controlMunicipality->id)
            ->sole();
        $primaryAnalyst = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL,
            )
            ->where('municipality_id', $municipality->id)
            ->sole();
        $noResponseApplication = Application::query()
            ->where(
                'application_number',
                MunicipalApplicationDemoProgram53Seeder::NO_RESPONSE_APPLICATION_NUMBER,
            )
            ->where('contest_id', $contest->id)
            ->sole();
        $controlApplication = Application::query()
            ->where('contest_id', $controlContest->id)
            ->sole();

        $batches = ApplicationReviewBatch::query()
            ->where('municipality_id', $municipality->id)
            ->where('contest_id', $contest->id)
            ->with(['items', 'publication.results'])
            ->orderBy('sequence_number')
            ->get();
        if (
            $batches->count() !== 2
            || $batches->contains(
                static fn (ApplicationReviewBatch $batch): bool => $batch->status
                    !== ApplicationReviewBatchStatus::Sealed,
            )
        ) {
            throw new LogicException(
                'Os dois lotes municipais demo não estão selados.',
            );
        }

        $hasher = app(CanonicalJsonHasher::class);
        foreach ($batches as $batch) {
            $items = $batch->items->sortBy('application_id')->values();
            foreach ($items as $item) {
                if (! hash_equals(
                    (string) $item->snapshot_hash,
                    $hasher->hash($item->snapshot_payload),
                )) {
                    throw new LogicException(
                        'Um item demo não corresponde ao snapshot imutável.',
                    );
                }
            }
            $batchPayload = [
                'schema_version' => 1,
                'contest_id' => $batch->contest_id,
                'cycle' => $batch->cycle->value,
                'items' => $items
                    ->map(static fn ($item): array => [
                        'application_id' => $item->application_id,
                        'snapshot_hash' => $item->snapshot_hash,
                        'payload' => $item->snapshot_payload,
                    ])
                    ->all(),
            ];
            if (! hash_equals(
                (string) $batch->snapshot_hash,
                $hasher->hash($batchPayload),
            )) {
                throw new LogicException(
                    'Um lote demo não corresponde ao hash canónico.',
                );
            }

            $publication = $batch->publication;
            if (
                ! $publication instanceof ApplicationReviewPublication
                || $publication->results->count() !== $batch->item_count
            ) {
                throw new LogicException(
                    'Um lote demo não possui publicação integral.',
                );
            }
            foreach ($publication->results as $result) {
                if (
                    ! hash_equals(
                        (string) $result->result_hash,
                        $hasher->hash($result->result_payload),
                    )
                    || ! $this->isSha256($result->notification_hash)
                ) {
                    throw new LogicException(
                        'Um resultado publicado demo perdeu integridade.',
                    );
                }
            }
        }

        $receipt = CorrectionSubmissionReceipt::query()
            ->where('application_id', $application->id)
            ->sole();
        if (! hash_equals(
            (string) $receipt->snapshot_hash,
            $hasher->hash($receipt->snapshot_payload),
        )) {
            throw new LogicException(
                'O recibo demo não corresponde ao snapshot canónico.',
            );
        }

        $expiredRequest = CorrectionRequest::query()
            ->where('application_id', $noResponseApplication->id)
            ->sole();
        if (
            $expiredRequest->status !== CorrectionRequestStatus::Expired
            || $expiredRequest->responses()->exists()
            || $expiredRequest->submissionReceipt()->exists()
        ) {
            throw new LogicException(
                'O controlo demo sem resposta não está expirado e vazio.',
            );
        }

        $exports = ReportExport::query()
            ->where('municipality_id', $municipality->id)
            ->where(
                'export_profile',
                TemporalApplicationResultExportService::PROFILE,
            )
            ->orderBy('export_mode')
            ->get();
        if ($exports->count() !== 2) {
            throw new LogicException(
                'O cenário demo exige duas exportações temporais.',
            );
        }
        $exportFiles = [];
        $exportSummary = [];
        foreach ($exports as $export) {
            if (
                $export->status !== ReportExportStatus::Completed
                || $export->sensitive_fields_included
                || $export->document_files_requested
                || $export->document_files_included
                || ! $this->isSha256($export->source_fingerprint)
                || ! $this->isSha256($export->manifest_sha256)
                || ! $this->isSha256($export->package_sha256)
                || trim($export->file_path) === ''
                || ! Storage::disk('local')->exists($export->file_path)
            ) {
                throw new LogicException(
                    'Uma exportação temporal demo é insegura ou incompleta.',
                );
            }

            $exportFiles[] = $export->file_path;
            $exportSummary[] = [
                'public_id' => (string) $export->public_id,
                'mode' => $export->export_mode?->value,
                'status' => $export->status->value,
                'datasets' => $export->datasets,
                'formats' => $export->formats,
                'package_sha256' => (string) $export->package_sha256,
            ];
        }
        $modes = $exports
            ->map(static fn (ReportExport $export): ?string => $export
                ->export_mode?->value)
            ->filter()
            ->sort()
            ->values()
            ->all();
        $expectedModes = [
            ApplicationResultExportMode::DeltaBetweenBatches->value,
            ApplicationResultExportMode::SealedBatch->value,
        ];
        sort($expectedModes, SORT_STRING);
        if ($modes !== $expectedModes) {
            throw new LogicException(
                'Os modos temporais demo não cobrem lote e delta.',
            );
        }

        $scope = app(MunicipalRecordScopeService::class);
        $primaryVisible = $scope
            ->applications(Application::query(), $primaryAnalyst)
            ->count();
        $controlVisible = $scope
            ->applications(Application::query(), $controlAnalyst)
            ->count();
        if (
            $primaryVisible !== 2
            || $controlVisible !== 1
            || $scope->ownsApplication($primaryAnalyst, $controlApplication)
            || $scope->ownsApplication($controlAnalyst, $application)
        ) {
            throw new LogicException(
                'O cenário demo não preserva o isolamento municipal.',
            );
        }

        $primaryBatchIds = $batches->pluck('id');
        $controlBatchIds = ApplicationReviewBatch::query()
            ->where('municipality_id', $controlMunicipality->id)
            ->where('contest_id', $controlContest->id)
            ->pluck('id');

        return [
            'counts' => [
                'municipalities' => Municipality::query()->count(),
                'contest_applications' => Application::query()
                    ->where('contest_id', $contest->id)
                    ->count(),
                'review_batches' => $batches->count(),
                'review_batch_items' => DB::table(
                    'application_review_batch_items',
                )->whereIn('application_review_batch_id', $primaryBatchIds)
                    ->count(),
                'review_publications' => DB::table(
                    'application_review_publications',
                )->whereIn('application_review_batch_id', $primaryBatchIds)
                    ->count(),
                'review_publication_results' => DB::table(
                    'application_review_publication_results',
                )->whereIn(
                    'application_review_publication_id',
                    DB::table('application_review_publications')
                        ->whereIn(
                            'application_review_batch_id',
                            $primaryBatchIds,
                        )
                        ->select('id'),
                )->count(),
                'correction_submission_receipts' => CorrectionSubmissionReceipt::query()
                    ->where('application_id', $application->id)
                    ->count(),
                'expired_without_response' => CorrectionRequest::query()
                    ->where('application_id', $noResponseApplication->id)
                    ->where(
                        'status',
                        CorrectionRequestStatus::Expired->value,
                    )
                    ->count(),
                'temporal_exports' => $exports->count(),
                'control_applications' => Application::query()
                    ->where('contest_id', $controlContest->id)
                    ->count(),
                'control_review_batches' => $controlBatchIds->count(),
                'control_publications' => DB::table(
                    'application_review_publications',
                )->whereIn(
                    'application_review_batch_id',
                    $controlBatchIds,
                )->count(),
            ],
            'municipality_isolation' => [
                'primary_visible_applications' => $primaryVisible,
                'control_visible_applications' => $controlVisible,
                'cross_access_denied' => true,
            ],
            'exports' => $exportSummary,
            'files' => $exportFiles,
        ];
    }

    private function isSha256(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\A[a-f0-9]{64}\z/', $value) === 1;
    }

    /**
     * @param  array<string, int>  $actual
     * @param  array<string, int>  $expected
     */
    private function assertCounts(
        array $actual,
        array $expected,
    ): void {
        foreach ($expected as $key => $expectedValue) {
            $actualValue = $actual[$key] ?? null;

            if ($actualValue !== $expectedValue) {
                throw new LogicException(
                    "O indicador demo {$key} deveria ser "
                    ."{$expectedValue}, mas é "
                    .($actualValue === null
                        ? 'inexistente'
                        : (string) $actualValue)
                    .'.',
                );
            }
        }
    }

    private function assertRawStatus(
        Model $model,
        string $attribute,
        string $expected,
        string $message,
    ): void {
        if (
            (string) $model->getRawOriginal($attribute)
                !== $expected
        ) {
            throw new LogicException($message);
        }
    }

    /**
     * @return list<array{
     *     name: string,
     *     email: string,
     *     role: string,
     *     mfa_required: bool
     * }>
     */
    private function accounts(int $municipalityId): array
    {
        $users = User::query()
            ->where('municipality_id', $municipalityId)
            ->whereIn('email', array_keys(self::ACCOUNT_ROLES))
            ->with('roles')
            ->get()
            ->keyBy('email');

        if ($users->count() !== count(self::ACCOUNT_ROLES)) {
            throw new LogicException(
                'As seis contas demo não estão disponíveis.',
            );
        }

        $accounts = [];

        foreach (self::ACCOUNT_ROLES as $email => $expectedRole) {
            $user = $users->get($email);

            if (! $user instanceof User) {
                throw new LogicException(
                    "A conta demo {$email} não existe.",
                );
            }

            $roleNames = $user->roles
                ->pluck('name')
                ->map(static fn ($name): string => (string) $name)
                ->sort()
                ->values()
                ->all();

            if ($roleNames !== [$expectedRole]) {
                throw new LogicException(
                    "A conta demo {$email} não possui apenas "
                    ."a role {$expectedRole}.",
                );
            }

            $accounts[] = [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => $expectedRole,
                'mfa_required' => (bool) $user->mfa_required,
            ];
        }

        return $accounts;
    }

    /**
     * @return array{
     *     email: string,
     *     role: string,
     *     template_key: string,
     *     template_version: string,
     *     template_fingerprint: string,
     *     mfa_required: bool,
     *     entitlements: list<string>,
     *     allowed_operations: list<string>,
     *     denied_operations: list<string>,
     *     global_scope: bool
     * }
     */
    private function program53Profile(Municipality $municipality): array
    {
        $user = User::query()
            ->where('municipality_id', $municipality->id)
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL,
            )
            ->with('roles.permissions')
            ->sole();
        $role = $user->roles->sole();
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');

        if (
            $role->template_key !== $template['key']
            || $role->template_version !== $template['version']
            || $role->template_fingerprint !== $template['fingerprint']
            || $role->scope !== 'municipal'
            || $role->is_system
        ) {
            throw new LogicException(
                'O perfil combinado demo não corresponde ao template municipal versionado.',
            );
        }

        $permissions = $role->permissions
            ->pluck('name')
            ->map(static fn ($name): string => (string) $name)
            ->sort()
            ->values()
            ->all();
        $expectedPermissions = $template['permissions'];
        sort($expectedPermissions, SORT_STRING);

        if ($permissions !== $expectedPermissions) {
            throw new LogicException(
                'A matriz efetiva do perfil combinado demo diverge do template.',
            );
        }

        $allowed = [
            'administrative_processes.view',
            'documents.approve',
            'documents.reject',
            'administrative_processes.update',
            'administrative_processes.publish',
            'applications.export',
            'reports.export',
            'reports.audit',
        ];
        $denied = [
            'reports.export_sensitive',
            'roles.view',
            'users.view',
            'platform_operators.view',
            'finance.view',
            'contracts.view',
            'rgpd.retention.view',
            '*',
        ];

        foreach ($allowed as $permission) {
            if (! $user->hasPermission($permission)) {
                throw new LogicException(
                    "O perfil combinado demo não possui {$permission}.",
                );
            }
        }

        foreach ($denied as $permission) {
            if ($user->hasPermission($permission)) {
                throw new LogicException(
                    "O perfil combinado demo possui indevidamente {$permission}.",
                );
            }
        }

        if (! $user->mfa_required) {
            throw new LogicException(
                'O perfil combinado demo deve exigir MFA.',
            );
        }

        if (PlatformOperatorAssignment::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->exists()) {
            throw new LogicException(
                'O perfil combinado demo não pode possuir scope global.',
            );
        }

        $entitlements = MunicipalityFeatureEntitlement::query()
            ->where('municipality_id', $municipality->id)
            ->where('enabled', true)
            ->pluck('feature_key')
            ->map(static fn ($feature): string => $feature instanceof FeatureKey
                ? $feature->value
                : (string) $feature)
            ->sort()
            ->values()
            ->all();
        $expectedEntitlements = [
            FeatureKey::ApplicationIntake->value,
            FeatureKey::ApplicationReview->value,
            FeatureKey::ApplicationExport->value,
        ];
        sort($expectedEntitlements, SORT_STRING);

        if ($entitlements !== $expectedEntitlements) {
            throw new LogicException(
                'O Município demo deve possuir apenas os entitlements necessários ao fluxo candidatural.',
            );
        }

        return [
            'email' => (string) $user->email,
            'role' => (string) $role->name,
            'template_key' => (string) $role->template_key,
            'template_version' => (string) $role->template_version,
            'template_fingerprint' => (string) $role->template_fingerprint,
            'mfa_required' => (bool) $user->mfa_required,
            'entitlements' => $entitlements,
            'allowed_operations' => $allowed,
            'denied_operations' => $denied,
            'global_scope' => false,
        ];
    }
}
