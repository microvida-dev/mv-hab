<?php

namespace App\Services\Municipalities;

use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\ApplicationReview;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\Document;
use App\Models\DocumentSubmission;
use App\Models\EligibilityCheck;
use App\Models\FutureApplicationDataReuse;
use App\Models\Household;
use App\Models\HousingApplication;
use App\Models\ReportAccessLog;
use App\Models\ReportDownloadLog;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\SimulationSession;
use App\Models\SimulatorConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MunicipalRecordScopeService
{
    /** @var list<string> */
    private const APPLICATION_REPORT_CODES = [
        'applications_by_contest',
        'application_status_summary',
    ];

    /**
     * @param  Builder<Citizen>  $query
     * @return Builder<Citizen>
     */
    public function citizens(Builder $query, User $user): Builder
    {
        return $this->directMunicipalScope($query, $user);
    }

    public function ownsCitizen(User $user, Citizen $citizen): bool
    {
        return $this->citizens(Citizen::query()->whereKey($citizen), $user)->exists();
    }

    /**
     * @param  Builder<Household>  $query
     * @return Builder<Household>
     */
    public function households(Builder $query, User $user): Builder
    {
        return $this->directMunicipalScope($query, $user);
    }

    public function ownsHousehold(User $user, Household $household): bool
    {
        return $this->households(Household::query()->whereKey($household), $user)->exists();
    }

    /**
     * @param  Builder<HousingApplication>  $query
     * @return Builder<HousingApplication>
     */
    public function housingApplications(Builder $query, User $user): Builder
    {
        return $this->directMunicipalScope($query, $user);
    }

    public function ownsHousingApplication(User $user, HousingApplication $application): bool
    {
        return $this->housingApplications(
            HousingApplication::query()->whereKey($application),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function documents(Builder $query, User $user): Builder
    {
        return $this->directMunicipalScope($query, $user);
    }

    public function ownsDocument(User $user, Document $document): bool
    {
        return $this->documents(Document::query()->whereKey($document), $user)->exists();
    }

    /**
     * @param  Builder<SimulatorConfiguration>  $query
     * @return Builder<SimulatorConfiguration>
     */
    public function simulatorConfigurations(Builder $query, User $user): Builder
    {
        return $this->directMunicipalScope($query, $user);
    }

    public function ownsSimulatorConfiguration(
        User $user,
        SimulatorConfiguration $configuration,
    ): bool {
        if (! $configuration->exists) {
            return $user->municipality_id !== null
                && $configuration->municipality_id === $user->municipality_id;
        }

        return $this->simulatorConfigurations(
            SimulatorConfiguration::query()->whereKey($configuration),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<SimulationSession>  $query
     * @return Builder<SimulationSession>
     */
    public function simulationSessions(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $sessions) use ($user): void {
            $sessions
                ->where('municipality_id', $user->municipality_id)
                ->orWhereHas('user', fn (Builder $owner): Builder => $owner
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('recommendedContests.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsSimulationSession(User $user, SimulationSession $session): bool
    {
        return $this->simulationSessions(
            SimulationSession::query()->whereKey($session),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function contracts(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $contracts) use ($user): void {
            $contracts
                ->whereHas('program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('candidate', fn (Builder $tenant): Builder => $tenant
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsContract(User $user, Contract $contract): bool
    {
        return $this->contracts(Contract::query()->whereKey($contract), $user)->exists();
    }

    /**
     * @param  Builder<FutureApplicationDataReuse>  $query
     * @return Builder<FutureApplicationDataReuse>
     */
    public function futureApplicationDataReuse(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $reuse) use ($user): void {
            $reuse
                ->whereHas('user', fn (Builder $owner): Builder => $owner
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('sourceApplication.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('targetApplication.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsFutureApplicationDataReuse(
        User $user,
        FutureApplicationDataReuse $reuse,
    ): bool {
        return $this->futureApplicationDataReuse(
            FutureApplicationDataReuse::query()->whereKey($reuse),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Application>  $query
     * @return Builder<Application>
     */
    public function applications(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'program',
            fn (Builder $program): Builder => $program->where('municipality_id', $user->municipality_id),
        );
    }

    public function ownsApplication(User $user, Application $application): bool
    {
        return $this->applications(Application::query()->whereKey($application), $user)->exists();
    }

    /**
     * @param  Builder<AdministrativeProcess>  $query
     * @return Builder<AdministrativeProcess>
     */
    public function administrativeProcesses(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'program',
            fn (Builder $program): Builder => $program->where('municipality_id', $user->municipality_id),
        );
    }

    public function ownsAdministrativeProcess(User $user, AdministrativeProcess $process): bool
    {
        return $this->administrativeProcesses(
            AdministrativeProcess::query()->whereKey($process),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DocumentSubmission>  $query
     * @return Builder<DocumentSubmission>
     */
    public function documentSubmissions(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $documents) use ($user): void {
            $documents
                ->whereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('applications.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhere(function (Builder $unattached) use ($user): void {
                    $unattached
                        ->whereNull('application_id')
                        ->whereDoesntHave('applications')
                        ->where(function (Builder $candidateContext) use ($user): void {
                            $candidateContext
                                ->whereHas('user', fn (Builder $candidate): Builder => $candidate
                                    ->where('municipality_id', $user->municipality_id))
                                ->orWhereHas('adhesionRegistration.user', fn (Builder $candidate): Builder => $candidate
                                    ->where('municipality_id', $user->municipality_id));
                        });
                });
        });
    }

    public function ownsDocumentSubmission(User $user, DocumentSubmission $submission): bool
    {
        return $this->documentSubmissions(
            DocumentSubmission::query()->whereKey($submission),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<EligibilityCheck>  $query
     * @return Builder<EligibilityCheck>
     */
    public function eligibilityChecks(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $checks) use ($user): void {
            $checks
                ->whereHas('program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsEligibilityCheck(User $user, EligibilityCheck $check): bool
    {
        return $this->eligibilityChecks(EligibilityCheck::query()->whereKey($check), $user)->exists();
    }

    public function ownsApplicationReview(User $user, ApplicationReview $review): bool
    {
        if ($user->municipality_id === null) {
            return false;
        }

        return ApplicationReview::query()
            ->whereKey($review)
            ->where(function (Builder $reviews) use ($user): void {
                $reviews
                    ->whereHas('application.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id))
                    ->orWhereHas('administrativeProcess.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->exists();
    }

    public function ownsApplicationReport(User $user, ApplicationReport $report): bool
    {
        return $this->applications(
            Application::query()->whereKey($report->application_id),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ReportRun>  $query
     * @return Builder<ReportRun>
     */
    public function reportRuns(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $runs) use ($user): void {
            $runs->whereHas('definition', fn (Builder $definition): Builder => $definition
                ->whereNotIn('code', self::APPLICATION_REPORT_CODES));

            if ($user->municipality_id !== null) {
                $runs->orWhere(function (Builder $applicationRuns) use ($user): void {
                    $applicationRuns
                        ->whereHas('definition', fn (Builder $definition): Builder => $definition
                            ->whereIn('code', self::APPLICATION_REPORT_CODES))
                        ->whereHas('user', fn (Builder $owner): Builder => $owner
                            ->where('municipality_id', $user->municipality_id));
                });
            }
        });
    }

    public function ownsReportRun(User $user, ReportRun $run): bool
    {
        return $this->reportRuns(ReportRun::query()->whereKey($run), $user)->exists();
    }

    /**
     * @param  Builder<ReportExport>  $query
     * @return Builder<ReportExport>
     */
    public function reportExports(
        Builder $query,
        User $user,
        bool $includeApplicationReports = true,
    ): Builder {
        if (! $includeApplicationReports) {
            return $query->whereHas('run.definition', fn (Builder $definition): Builder => $definition
                ->whereNotIn('code', self::APPLICATION_REPORT_CODES));
        }

        return $query->whereIn(
            'report_run_id',
            $this->reportRuns(ReportRun::query(), $user)->select('id'),
        );
    }

    public function ownsReportExport(User $user, ReportExport $export): bool
    {
        return $this->reportExports(ReportExport::query()->whereKey($export), $user)->exists();
    }

    /**
     * @param  Builder<ReportAccessLog>  $query
     * @return Builder<ReportAccessLog>
     */
    public function reportAccessLogs(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $logs) use ($user): void {
            $logs
                ->whereNull('report_definition_id')
                ->orWhereHas('definition', fn (Builder $definition): Builder => $definition
                    ->whereNotIn('code', self::APPLICATION_REPORT_CODES));

            if ($user->municipality_id !== null) {
                $logs->orWhere(function (Builder $applicationLogs) use ($user): void {
                    $applicationLogs
                        ->whereHas('definition', fn (Builder $definition): Builder => $definition
                            ->whereIn('code', self::APPLICATION_REPORT_CODES))
                        ->whereHas('user', fn (Builder $owner): Builder => $owner
                            ->where('municipality_id', $user->municipality_id));
                });
            }
        });
    }

    /**
     * @param  Builder<ReportDownloadLog>  $query
     * @return Builder<ReportDownloadLog>
     */
    public function reportDownloadLogs(
        Builder $query,
        User $user,
        bool $includeApplicationReports = true,
    ): Builder {
        return $query->whereIn(
            'report_export_id',
            $this->reportExports(
                ReportExport::query(),
                $user,
                $includeApplicationReports,
            )->select('id'),
        );
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function directMunicipalScope(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('municipality_id', $user->municipality_id);
    }
}
