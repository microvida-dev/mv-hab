<?php

namespace App\Services\Demo;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReportStatus;
use App\Enums\ApplicationStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseStatus;
use App\Enums\DocumentDossierStatus;
use App\Enums\DocumentStatus;
use App\Enums\VisitStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\Contest;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\DocumentDossier;
use App\Models\HousingVisit;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCatalogSeeder;
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

        $expectedCounts = [
            'municipal_roles' => 4,
            'demo_users' => 5,
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
            'official_notifications' => 6,
            'candidate_interactions' => 3,
            'work_tasks' => 1,
            'document_ai_analyses' => 0,
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
            'files' => [
                'reports' => $reportPaths,
                'document_dossier' => $dossierPath,
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
                'As cinco contas demo não estão disponíveis.',
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
}
