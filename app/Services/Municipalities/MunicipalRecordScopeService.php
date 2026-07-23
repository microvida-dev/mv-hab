<?php

namespace App\Services\Municipalities;

use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessNote;
use App\Models\AdministrativeTask;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\ApplicationReview;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\Citizen;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\Document;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\DocumentAiScore;
use App\Models\DocumentAiSuggestion;
use App\Models\DocumentAiValidation;
use App\Models\DocumentAiValidationRun;
use App\Models\DocumentSubmission;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\EligibilityCheck;
use App\Models\FutureApplicationDataReuse;
use App\Models\GeneratedOfficialDocument;
use App\Models\GeneratedProcedureDocument;
use App\Models\Household;
use App\Models\HousingApplication;
use App\Models\LeaseContractDocument;
use App\Models\Program;
use App\Models\ReportAccessLog;
use App\Models\ReportDownloadLog;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\SimulationSession;
use App\Models\SimulatorConfiguration;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Platform\PlatformOperatorScopeService;
use Illuminate\Database\Eloquent\Builder;

class MunicipalRecordScopeService
{
    /** @var list<string> */
    private const APPLICATION_REPORT_CODES = [
        'applications_by_contest',
        'application_status_summary',
    ];

    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

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
     * @param  Builder<Program>  $query
     * @return Builder<Program>
     */
    public function programs(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        return $this->directMunicipalScope($query, $user);
    }

    public function ownsProgram(User $user, Program $program): bool
    {
        return $this->programs(Program::query()->whereKey($program), $user)->exists();
    }

    /**
     * @param  Builder<Contest>  $query
     * @return Builder<Contest>
     */
    public function contests(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'program',
            fn (Builder $program): Builder => $program
                ->where('municipality_id', $user->municipality_id),
        );
    }

    public function ownsContest(User $user, Contest $contest): bool
    {
        return $this->contests(Contest::query()->whereKey($contest), $user)->exists();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function users(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        return $this->directMunicipalScope($query, $user);
    }

    public function ownsUser(User $actor, User $target): bool
    {
        return $this->users(User::query()->whereKey($target), $actor)->exists();
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
     * @param  Builder<AdministrativeProcessNote>  $query
     * @return Builder<AdministrativeProcessNote>
     */
    public function administrativeProcessNotes(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $notes) use ($user): void {
            $notes
                ->whereHas('administrativeProcess.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsAdministrativeProcessNote(
        User $user,
        AdministrativeProcessNote $note,
    ): bool {
        return $this->administrativeProcessNotes(
            AdministrativeProcessNote::query()->whereKey($note),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<AdministrativeTask>  $query
     * @return Builder<AdministrativeTask>
     */
    public function administrativeTasks(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $tasks) use ($user): void {
            $tasks
                ->whereHas('administrativeProcess.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsAdministrativeTask(User $user, AdministrativeTask $task): bool
    {
        return $this->administrativeTasks(
            AdministrativeTask::query()->whereKey($task),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ApplicationSimulationInconsistency>  $query
     * @return Builder<ApplicationSimulationInconsistency>
     */
    public function applicationSimulationInconsistencies(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inconsistencies) use ($user): void {
            $inconsistencies
                ->whereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('user', fn (Builder $candidate): Builder => $candidate
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereIn(
                    'simulation_session_id',
                    $this->simulationSessions(SimulationSession::query(), $user)->select('id'),
                );
        });
    }

    public function ownsApplicationSimulationInconsistency(
        User $user,
        ApplicationSimulationInconsistency $inconsistency,
    ): bool {
        return $this->applicationSimulationInconsistencies(
            ApplicationSimulationInconsistency::query()->whereKey($inconsistency),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<CorrectionRequest>  $query
     * @return Builder<CorrectionRequest>
     */
    public function correctionRequests(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $requests) use ($user): void {
            $requests
                ->whereHas('administrativeProcess.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsCorrectionRequest(User $user, CorrectionRequest $request): bool
    {
        return $this->correctionRequests(
            CorrectionRequest::query()->whereKey($request),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<CorrectionResponse>  $query
     * @return Builder<CorrectionResponse>
     */
    public function correctionResponses(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $responses) use ($user): void {
            $responses
                ->whereHas('correctionRequest.administrativeProcess.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsCorrectionResponse(User $user, CorrectionResponse $response): bool
    {
        return $this->correctionResponses(
            CorrectionResponse::query()->whereKey($response),
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
     * @param  Builder<DocumentTemplate>  $query
     * @return Builder<DocumentTemplate>
     */
    public function documentTemplates(
        Builder $query,
        User $user,
        bool $includeGlobalCatalog = true,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $templates) use ($includeGlobalCatalog, $user): void {
            $templates->where('municipality_id', $user->municipality_id);

            if ($includeGlobalCatalog) {
                $templates->orWhereNull('municipality_id');
            }
        });
    }

    public function ownsDocumentTemplate(
        User $user,
        DocumentTemplate $template,
        bool $includeGlobalCatalog = true,
    ): bool {
        return $this->documentTemplates(
            DocumentTemplate::query()->whereKey($template),
            $user,
            $includeGlobalCatalog,
        )->exists();
    }

    public function canMutateDocumentTemplate(User $user, DocumentTemplate $template): bool
    {
        if ($template->municipality_id === null) {
            return $this->platformScope->hasGlobalScope($user);
        }

        return $user->municipality_id !== null
            && $template->municipality_id === $user->municipality_id;
    }

    public function ownsDocumentTemplateVersion(
        User $user,
        DocumentTemplateVersion $version,
        bool $includeGlobalCatalog = true,
    ): bool {
        return DocumentTemplateVersion::query()
            ->whereKey($version)
            ->whereIn(
                'document_template_id',
                $this->documentTemplates(
                    DocumentTemplate::query(),
                    $user,
                    $includeGlobalCatalog,
                )->select('id'),
            )
            ->exists();
    }

    public function canMutateDocumentTemplateVersion(
        User $user,
        DocumentTemplateVersion $version,
    ): bool {
        $version->loadMissing('template');

        return $version->template instanceof DocumentTemplate
            && $this->canMutateDocumentTemplate($user, $version->template);
    }

    /**
     * @param  Builder<GeneratedOfficialDocument>  $query
     * @return Builder<GeneratedOfficialDocument>
     */
    public function generatedOfficialDocuments(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $documents) use ($user): void {
            $documents
                ->whereHas('template', fn (Builder $templates): Builder => $templates
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('recipient', fn (Builder $recipients): Builder => $recipients
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('generatedBy', fn (Builder $generators): Builder => $generators
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsGeneratedOfficialDocument(
        User $user,
        GeneratedOfficialDocument $document,
    ): bool {
        return $this->generatedOfficialDocuments(
            GeneratedOfficialDocument::query()->whereKey($document),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<GeneratedProcedureDocument>  $query
     * @return Builder<GeneratedProcedureDocument>
     */
    public function generatedProcedureDocuments(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $documents) use ($user): void {
            $documents
                ->whereHas('program', fn (Builder $programs): Builder => $programs
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('contest.program', fn (Builder $programs): Builder => $programs
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $programs): Builder => $programs
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsGeneratedProcedureDocument(
        User $user,
        GeneratedProcedureDocument $document,
    ): bool {
        return $this->generatedProcedureDocuments(
            GeneratedProcedureDocument::query()->whereKey($document),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<LeaseContractDocument>  $query
     * @return Builder<LeaseContractDocument>
     */
    public function leaseContractDocuments(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'lease_contract_id',
            $this->contracts(Contract::query(), $user)->select('id'),
        );
    }

    public function ownsLeaseContractDocument(
        User $user,
        LeaseContractDocument $document,
    ): bool {
        return $this->leaseContractDocuments(
            LeaseContractDocument::query()->whereKey($document),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<TenantFinancialAccount>  $query
     * @return Builder<TenantFinancialAccount>
     */
    public function tenantFinancialAccounts(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'lease_contract_id',
            $this->contracts(Contract::query(), $user)->select('id'),
        );
    }

    public function ownsTenantFinancialAccount(
        User $user,
        TenantFinancialAccount $account,
    ): bool {
        return $this->tenantFinancialAccounts(
            TenantFinancialAccount::query()->whereKey($account),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<AnnualDocumentUpdateRequest>  $query
     * @return Builder<AnnualDocumentUpdateRequest>
     */
    public function annualDocumentUpdateRequests(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'lease_contract_id',
            $this->contracts(Contract::query(), $user)->select('id'),
        );
    }

    public function ownsAnnualDocumentUpdateRequest(
        User $user,
        AnnualDocumentUpdateRequest $request,
    ): bool {
        return $this->annualDocumentUpdateRequests(
            AnnualDocumentUpdateRequest::query()->whereKey($request),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DocumentAiAnalysis>  $query
     * @return Builder<DocumentAiAnalysis>
     */
    public function documentAiAnalyses(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'document_submission_id',
            $this->documentSubmissions(DocumentSubmission::query(), $user)->select('id'),
        );
    }

    public function ownsDocumentAiAnalysis(User $user, DocumentAiAnalysis $analysis): bool
    {
        return $this->documentAiAnalyses(
            DocumentAiAnalysis::query()->whereKey($analysis),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DocumentAiScore>  $query
     * @return Builder<DocumentAiScore>
     */
    public function documentAiScores(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'document_ai_analysis_id',
            $this->documentAiAnalyses(DocumentAiAnalysis::query(), $user)->select('id'),
        );
    }

    public function ownsDocumentAiScore(User $user, DocumentAiScore $score): bool
    {
        return $this->documentAiScores(
            DocumentAiScore::query()->whereKey($score),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DocumentAiSuggestion>  $query
     * @return Builder<DocumentAiSuggestion>
     */
    public function documentAiSuggestions(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'document_ai_analysis_id',
            $this->documentAiAnalyses(DocumentAiAnalysis::query(), $user)->select('id'),
        );
    }

    public function ownsDocumentAiSuggestion(User $user, DocumentAiSuggestion $suggestion): bool
    {
        return $this->documentAiSuggestions(
            DocumentAiSuggestion::query()->whereKey($suggestion),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DocumentAiField>  $query
     * @return Builder<DocumentAiField>
     */
    public function documentAiFields(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'document_ai_analysis_id',
            $this->documentAiAnalyses(DocumentAiAnalysis::query(), $user)->select('id'),
        );
    }

    public function ownsDocumentAiField(User $user, DocumentAiField $field): bool
    {
        return $this->documentAiFields(
            DocumentAiField::query()->whereKey($field),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DocumentAiValidationRun>  $query
     * @return Builder<DocumentAiValidationRun>
     */
    public function documentAiValidationRuns(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'application_id',
            $this->applications(Application::query(), $user)->select('id'),
        );
    }

    public function ownsDocumentAiValidationRun(User $user, DocumentAiValidationRun $run): bool
    {
        return $this->documentAiValidationRuns(
            DocumentAiValidationRun::query()->whereKey($run),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DocumentAiValidation>  $query
     * @return Builder<DocumentAiValidation>
     */
    public function documentAiValidations(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'document_ai_validation_run_id',
            $this->documentAiValidationRuns(DocumentAiValidationRun::query(), $user)->select('id'),
        );
    }

    public function ownsDocumentAiValidation(User $user, DocumentAiValidation $validation): bool
    {
        return $this->documentAiValidations(
            DocumentAiValidation::query()->whereKey($validation),
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
