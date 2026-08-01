<?php

namespace App\Services\Municipalities;

use App\Models\AdditionalInformationRequest;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessNote;
use App\Models\AdministrativeTask;
use App\Models\AdministrativeWorkflowConfig;
use App\Models\Allocation;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\ApplicationReview;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\ApplicationScore;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\Arrear;
use App\Models\Citizen;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\CommunicationReceipt;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\Contest;
use App\Models\ContestClosure;
use App\Models\ContextualFaq;
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractDeposit;
use App\Models\ContractTemplate;
use App\Models\ControlledWithdrawal;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\DefaultNotice;
use App\Models\DefinitiveList;
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
use App\Models\DrawConvocation;
use App\Models\EligibilityCheck;
use App\Models\EligibilityCriterion;
use App\Models\EligibilityRuleSet;
use App\Models\FutureApplicationDataReuse;
use App\Models\GeneratedOfficialDocument;
use App\Models\GeneratedProcedureDocument;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\Household;
use App\Models\HousingApplication;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\IncomeChangeDeclaration;
use App\Models\InspectionChecklistTemplate;
use App\Models\InternalAlert;
use App\Models\KeyHandoverAppointment;
use App\Models\LeaseContractDocument;
use App\Models\LeaseContractValidation;
use App\Models\LeasePayment;
use App\Models\ListAutomationRun;
use App\Models\LotteryDraw;
use App\Models\LotteryResult;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSupplier;
use App\Models\MunicipalTeam;
use App\Models\NotificationEventRule;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\OfficialNotification;
use App\Models\Payment;
use App\Models\PaymentImportBatch;
use App\Models\PaymentReceipt;
use App\Models\PostDrawReport;
use App\Models\ProcedureMinute;
use App\Models\Program;
use App\Models\PropertyInspection;
use App\Models\ProvisionalList;
use App\Models\RankingSnapshot;
use App\Models\RankingUpdateRun;
use App\Models\RegularizationAgreement;
use App\Models\RentCalculation;
use App\Models\RentInstallment;
use App\Models\RentManualReview;
use App\Models\RentReview;
use App\Models\RentRule;
use App\Models\RentRuleSet;
use App\Models\RentSchedule;
use App\Models\ReportAccessLog;
use App\Models\ReportDownloadLog;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\ScoringCriterion;
use App\Models\ScoringRule;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\SimulationSession;
use App\Models\SimulatorConfiguration;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\TenantChargeRun;
use App\Models\TenantCommunication;
use App\Models\TenantFinancialAccount;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantTransition;
use App\Models\TieBreakerRule;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Models\WinnerRegistration;
use App\Models\WorkTask;
use App\Services\Platform\PlatformOperatorScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public function hasMunicipalOrGlobalScope(User $user): bool
    {
        return $user->municipality_id !== null
            || $this->platformScope->hasGlobalScope($user);
    }

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
     * @param  Builder<Allocation>  $query
     * @return Builder<Allocation>
     */
    public function allocations(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $allocations) use ($user): void {
            $allocations
                ->whereHas('program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsAllocation(User $user, Allocation $allocation): bool
    {
        return $this->allocations(Allocation::query()->whereKey($allocation), $user)->exists();
    }

    /**
     * @param  Builder<RentCalculation>  $query
     * @return Builder<RentCalculation>
     */
    public function rentCalculations(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $calculations) use ($user): void {
            $calculations
                ->whereIn(
                    'allocation_id',
                    $this->allocations(Allocation::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsRentCalculation(User $user, RentCalculation $calculation): bool
    {
        return $this->rentCalculations(
            RentCalculation::query()->whereKey($calculation),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<WinnerRegistration>  $query
     * @return Builder<WinnerRegistration>
     */
    public function winnerRegistrations(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $winners) use ($user): void {
            $winners
                ->whereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('lotteryDraw.contest.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsWinnerRegistration(User $user, WinnerRegistration $winner): bool
    {
        return $this->winnerRegistrations(
            WinnerRegistration::query()->whereKey($winner),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<HousingUnit>  $query
     * @return Builder<HousingUnit>
     */
    public function housingUnits(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        return $this->directMunicipalScope($query, $user);
    }

    public function ownsHousingUnit(User $user, HousingUnit $housingUnit): bool
    {
        return $this->housingUnits(
            HousingUnit::query()->whereKey($housingUnit),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function contracts(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $contracts) use ($user): void {
            $contracts
                ->whereHas('program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('housingUnit', fn (Builder $housingUnit): Builder => $housingUnit
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('citizen', fn (Builder $citizen): Builder => $citizen
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
     * @param  Builder<ContractTemplate>  $query
     * @return Builder<ContractTemplate>
     */
    public function contractTemplates(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $templates) use ($user): void {
            $templates
                ->whereHas('program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsContractTemplate(User $user, ContractTemplate $template): bool
    {
        return $this->contractTemplates(
            ContractTemplate::query()->whereKey($template),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ContractClause>  $query
     * @return Builder<ContractClause>
     */
    public function contractClauses(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $clauses) use ($user): void {
            $clauses
                ->whereHas('program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsContractClause(User $user, ContractClause $clause): bool
    {
        return $this->contractClauses(
            ContractClause::query()->whereKey($clause),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<LeaseContractValidation>  $query
     * @return Builder<LeaseContractValidation>
     */
    public function leaseContractValidations(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'lease_contract_id',
            $this->contracts(Contract::query(), $user)->select('id'),
        );
    }

    public function ownsLeaseContractValidation(
        User $user,
        LeaseContractValidation $validation,
    ): bool {
        return $this->leaseContractValidations(
            LeaseContractValidation::query()->whereKey($validation),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<KeyHandoverAppointment>  $query
     * @return Builder<KeyHandoverAppointment>
     */
    public function keyHandoverAppointments(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $appointments) use ($user): void {
            $appointments
                ->whereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsKeyHandoverAppointment(
        User $user,
        KeyHandoverAppointment $appointment,
    ): bool {
        return $this->keyHandoverAppointments(
            KeyHandoverAppointment::query()->whereKey($appointment),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<TenantTransition>  $query
     * @return Builder<TenantTransition>
     */
    public function tenantTransitions(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $transitions) use ($user): void {
            $transitions
                ->whereHas('application.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                );
        });
    }

    public function ownsTenantTransition(User $user, TenantTransition $transition): bool
    {
        return $this->tenantTransitions(
            TenantTransition::query()->whereKey($transition),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<TenantCommunication>  $query
     * @return Builder<TenantCommunication>
     */
    public function tenantCommunications(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $communications) use ($user): void {
            $communications
                ->whereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereHas('tenant', fn (Builder $tenant): Builder => $tenant
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsTenantCommunication(
        User $user,
        TenantCommunication $communication,
    ): bool {
        return $this->tenantCommunications(
            TenantCommunication::query()->whereKey($communication),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<TenantChargeRun>  $query
     * @return Builder<TenantChargeRun>
     */
    public function tenantChargeRuns(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $runs) use ($user): void {
            $runs
                ->whereHas('items', fn (Builder $items): Builder => $items
                    ->whereIn(
                        'lease_contract_id',
                        $this->contracts(Contract::query(), $user)->select('id'),
                    ))
                ->orWhere(function (Builder $emptyRuns) use ($user): void {
                    $emptyRuns
                        ->whereDoesntHave('items')
                        ->whereHas('createdBy', fn (Builder $creator): Builder => $creator
                            ->where('municipality_id', $user->municipality_id));
                });
        });
    }

    public function ownsTenantChargeRun(User $user, TenantChargeRun $run): bool
    {
        return $this->tenantChargeRuns(
            TenantChargeRun::query()->whereKey($run),
            $user,
        )->exists();
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
     * @param  Builder<MunicipalTeam>  $query
     * @return Builder<MunicipalTeam>
     */
    public function municipalTeams(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        return $this->directMunicipalScope($query, $user);
    }

    public function ownsMunicipalTeam(User $user, MunicipalTeam $team): bool
    {
        return $this->municipalTeams(
            MunicipalTeam::query()->whereKey($team),
            $user,
        )->exists();
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
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

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
     * @param  Builder<AdministrativeDecision>  $query
     * @return Builder<AdministrativeDecision>
     */
    public function administrativeDecisions(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $decisions) use ($user): void {
            $decisions
                ->whereIn(
                    'administrative_process_id',
                    $this->administrativeProcesses(
                        AdministrativeProcess::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'application_id',
                    $this->applications(Application::query(), $user)->select('id'),
                );
        });
    }

    public function ownsAdministrativeDecision(
        User $user,
        AdministrativeDecision $decision,
    ): bool {
        return $this->administrativeDecisions(
            AdministrativeDecision::query()->whereKey($decision),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Complaint>  $query
     * @return Builder<Complaint>
     */
    public function complaints(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'application_id',
            $this->applications(Application::query(), $user)->select('id'),
        );
    }

    public function ownsComplaint(User $user, Complaint $complaint): bool
    {
        return $this->complaints(Complaint::query()->whereKey($complaint), $user)->exists();
    }

    /**
     * @param  Builder<ComplaintDecision>  $query
     * @return Builder<ComplaintDecision>
     */
    public function complaintDecisions(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'complaint_id',
            $this->complaints(Complaint::query(), $user)->select('id'),
        );
    }

    public function ownsComplaintDecision(User $user, ComplaintDecision $decision): bool
    {
        return $this->complaintDecisions(
            ComplaintDecision::query()->whereKey($decision),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Hearing>  $query
     * @return Builder<Hearing>
     */
    public function hearings(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'application_id',
            $this->applications(Application::query(), $user)->select('id'),
        );
    }

    public function ownsHearing(User $user, Hearing $hearing): bool
    {
        return $this->hearings(Hearing::query()->whereKey($hearing), $user)->exists();
    }

    /**
     * @param  Builder<HearingSubmission>  $query
     * @return Builder<HearingSubmission>
     */
    public function hearingSubmissions(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'hearing_id',
            $this->hearings(Hearing::query(), $user)->select('id'),
        );
    }

    public function ownsHearingSubmission(User $user, HearingSubmission $submission): bool
    {
        return $this->hearingSubmissions(
            HearingSubmission::query()->whereKey($submission),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<AdditionalInformationRequest>  $query
     * @return Builder<AdditionalInformationRequest>
     */
    public function additionalInformationRequests(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'complaint_id',
            $this->complaints(Complaint::query(), $user)->select('id'),
        );
    }

    public function ownsAdditionalInformationRequest(
        User $user,
        AdditionalInformationRequest $request,
    ): bool {
        return $this->additionalInformationRequests(
            AdditionalInformationRequest::query()->whereKey($request),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ProvisionalList>  $query
     * @return Builder<ProvisionalList>
     */
    public function provisionalLists(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'contest_id',
            $this->contests(Contest::query(), $user)->select('id'),
        );
    }

    public function ownsProvisionalList(User $user, ProvisionalList $list): bool
    {
        return $this->provisionalLists(
            ProvisionalList::query()->whereKey($list),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DefinitiveList>  $query
     * @return Builder<DefinitiveList>
     */
    public function definitiveLists(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'contest_id',
            $this->contests(Contest::query(), $user)->select('id'),
        );
    }

    public function ownsDefinitiveList(User $user, DefinitiveList $list): bool
    {
        return $this->definitiveLists(
            DefinitiveList::query()->whereKey($list),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<AllocationRun>  $query
     * @return Builder<AllocationRun>
     */
    public function allocationRuns(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'contest_id',
            $this->contests(Contest::query(), $user)->select('id'),
        );
    }

    public function ownsAllocationRun(User $user, AllocationRun $run): bool
    {
        return $this->allocationRuns(
            AllocationRun::query()->whereKey($run),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ListAutomationRun>  $query
     * @return Builder<ListAutomationRun>
     */
    public function listAutomationRuns(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'contest_id',
            $this->contests(Contest::query(), $user)->select('id'),
        );
    }

    public function ownsListAutomationRun(User $user, ListAutomationRun $run): bool
    {
        return $this->listAutomationRuns(
            ListAutomationRun::query()->whereKey($run),
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
     * @param  Builder<ApplicationReviewPublicationResult>  $query
     * @return Builder<ApplicationReviewPublicationResult>
     */
    public function applicationReviewPublicationResults(
        Builder $query,
        User $user,
    ): Builder {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('municipality_id', $user->municipality_id);
    }

    public function ownsApplicationReviewPublicationResult(
        User $user,
        ApplicationReviewPublicationResult $result,
    ): bool {
        return $this->applicationReviewPublicationResults(
            ApplicationReviewPublicationResult::query()->whereKey($result),
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

        return $query->whereHas(
            'publicationResult',
            fn (Builder $result): Builder => $result
                ->where('municipality_id', $user->municipality_id),
        );
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
     * @param  Builder<ProcedureMinute>  $query
     * @return Builder<ProcedureMinute>
     */
    public function procedureMinutes(
        Builder $query,
        User $user,
    ): Builder {
        $query->where(function (Builder $minutes): void {
            $minutes
                ->whereNotNull('program_id')
                ->orWhereNotNull('contest_id')
                ->orWhereNotNull('application_id');
        });

        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where(function (Builder $minutes) use ($user): void {
                $minutes
                    ->whereNull('program_id')
                    ->orWhereHas('program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $minutes) use ($user): void {
                $minutes
                    ->whereNull('contest_id')
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $minutes) use ($user): void {
                $minutes
                    ->whereNull('application_id')
                    ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            });
    }

    public function ownsProcedureMinute(
        User $user,
        ProcedureMinute $minute,
    ): bool {
        return $this->procedureMinutes(
            ProcedureMinute::query()->whereKey($minute),
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
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $accounts) use ($user): void {
            $accounts
                ->whereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'application_id',
                    $this->applications(Application::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'allocation_id',
                    $this->allocations(Allocation::query(), $user)->select('id'),
                )
                ->orWhereHas('housingUnit', fn (Builder $housingUnit): Builder => $housingUnit
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('tenant', fn (Builder $tenant): Builder => $tenant
                    ->where('municipality_id', $user->municipality_id));
        });
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
     * @param  Builder<ContractDeposit>  $query
     * @return Builder<ContractDeposit>
     */
    public function contractDeposits(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $deposits) use ($user): void {
            $deposits
                ->whereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'application_id',
                    $this->applications(Application::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'allocation_id',
                    $this->allocations(Allocation::query(), $user)->select('id'),
                );
        });
    }

    public function ownsContractDeposit(User $user, ContractDeposit $deposit): bool
    {
        return $this->contractDeposits(
            ContractDeposit::query()->whereKey($deposit),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RentManualReview>  $query
     * @return Builder<RentManualReview>
     */
    public function rentManualReviews(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'rent_calculation_id',
            $this->rentCalculations(RentCalculation::query(), $user)->select('id'),
        );
    }

    public function ownsRentManualReview(User $user, RentManualReview $review): bool
    {
        return $this->rentManualReviews(
            RentManualReview::query()->whereKey($review),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RentRuleSet>  $query
     * @return Builder<RentRuleSet>
     */
    public function rentRuleSets(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $ruleSets) use ($user): void {
            $ruleSets
                ->whereHas('program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id))
                ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                    ->where('municipality_id', $user->municipality_id));
        });
    }

    public function ownsRentRuleSet(User $user, RentRuleSet $ruleSet): bool
    {
        return $this->rentRuleSets(
            RentRuleSet::query()->whereKey($ruleSet),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<TypologyAdequacyRule>  $query
     * @return Builder<TypologyAdequacyRule>
     */
    public function typologyAdequacyRules(Builder $query, User $user): Builder
    {
        return $this->ruleSetsThroughProgramOrContest($query, $user);
    }

    public function ownsTypologyAdequacyRule(
        User $user,
        TypologyAdequacyRule $rule,
    ): bool {
        return $this->typologyAdequacyRules(
            TypologyAdequacyRule::query()->whereKey($rule),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<AllocationRuleSet>  $query
     * @return Builder<AllocationRuleSet>
     */
    public function allocationRuleSets(Builder $query, User $user): Builder
    {
        return $this->ruleSetsThroughProgramOrContest($query, $user);
    }

    public function ownsAllocationRuleSet(
        User $user,
        AllocationRuleSet $ruleSet,
    ): bool {
        return $this->allocationRuleSets(
            AllocationRuleSet::query()->whereKey($ruleSet),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RentRule>  $query
     * @return Builder<RentRule>
     */
    public function rentRules(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'rent_rule_set_id',
            $this->rentRuleSets(RentRuleSet::query(), $user)->select('id'),
        );
    }

    public function ownsRentRule(User $user, RentRule $rule): bool
    {
        return $this->rentRules(RentRule::query()->whereKey($rule), $user)->exists();
    }

    /**
     * @param  Builder<RentSchedule>  $query
     * @return Builder<RentSchedule>
     */
    public function rentSchedules(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $schedules) use ($user): void {
            $schedules
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                );
        });
    }

    public function ownsRentSchedule(User $user, RentSchedule $schedule): bool
    {
        return $this->rentSchedules(
            RentSchedule::query()->whereKey($schedule),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RentInstallment>  $query
     * @return Builder<RentInstallment>
     */
    public function rentInstallments(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $installments) use ($user): void {
            $installments
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'rent_schedule_id',
                    $this->rentSchedules(RentSchedule::query(), $user)->select('id'),
                );
        });
    }

    public function ownsRentInstallment(User $user, RentInstallment $installment): bool
    {
        return $this->rentInstallments(
            RentInstallment::query()->whereKey($installment),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<LeasePayment>  $query
     * @return Builder<LeasePayment>
     */
    public function leasePayments(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $payments) use ($user): void {
            $payments
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                );
        });
    }

    public function ownsLeasePayment(User $user, LeasePayment $payment): bool
    {
        return $this->leasePayments(
            LeasePayment::query()->whereKey($payment),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<PaymentReceipt>  $query
     * @return Builder<PaymentReceipt>
     */
    public function paymentReceipts(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $receipts) use ($user): void {
            $receipts
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'lease_payment_id',
                    $this->leasePayments(LeasePayment::query(), $user)->select('id'),
                );
        });
    }

    public function ownsPaymentReceipt(User $user, PaymentReceipt $receipt): bool
    {
        return $this->paymentReceipts(
            PaymentReceipt::query()->whereKey($receipt),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Arrear>  $query
     * @return Builder<Arrear>
     */
    public function arrears(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $arrears) use ($user): void {
            $arrears
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'rent_installment_id',
                    $this->rentInstallments(RentInstallment::query(), $user)->select('id'),
                );
        });
    }

    public function ownsArrear(User $user, Arrear $arrear): bool
    {
        return $this->arrears(Arrear::query()->whereKey($arrear), $user)->exists();
    }

    /**
     * @param  Builder<DefaultNotice>  $query
     * @return Builder<DefaultNotice>
     */
    public function defaultNotices(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $notices) use ($user): void {
            $notices
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'arrear_id',
                    $this->arrears(Arrear::query(), $user)->select('id'),
                );
        });
    }

    public function ownsDefaultNotice(User $user, DefaultNotice $notice): bool
    {
        return $this->defaultNotices(
            DefaultNotice::query()->whereKey($notice),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RegularizationAgreement>  $query
     * @return Builder<RegularizationAgreement>
     */
    public function regularizationAgreements(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $agreements) use ($user): void {
            $agreements
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                );
        });
    }

    public function ownsRegularizationAgreement(
        User $user,
        RegularizationAgreement $agreement,
    ): bool {
        return $this->regularizationAgreements(
            RegularizationAgreement::query()->whereKey($agreement),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RentReview>  $query
     * @return Builder<RentReview>
     */
    public function rentReviews(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $reviews) use ($user): void {
            $reviews
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                );
        });
    }

    public function ownsRentReview(User $user, RentReview $review): bool
    {
        return $this->rentReviews(RentReview::query()->whereKey($review), $user)->exists();
    }

    /**
     * @param  Builder<IncomeChangeDeclaration>  $query
     * @return Builder<IncomeChangeDeclaration>
     */
    public function incomeChangeDeclarations(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $declarations) use ($user): void {
            $declarations
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'rent_review_id',
                    $this->rentReviews(RentReview::query(), $user)->select('id'),
                );
        });
    }

    public function ownsIncomeChangeDeclaration(
        User $user,
        IncomeChangeDeclaration $declaration,
    ): bool {
        return $this->incomeChangeDeclarations(
            IncomeChangeDeclaration::query()->whereKey($declaration),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<PaymentImportBatch>  $query
     * @return Builder<PaymentImportBatch>
     */
    public function paymentImportBatches(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('municipality_id', $user->municipality_id);
    }

    public function ownsPaymentImportBatch(User $user, PaymentImportBatch $batch): bool
    {
        return $this->paymentImportBatches(
            PaymentImportBatch::query()->whereKey($batch),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<TenantInvoice>  $query
     * @return Builder<TenantInvoice>
     */
    public function tenantInvoices(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $invoices) use ($user): void {
            $invoices
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                );
        });
    }

    public function ownsTenantInvoice(User $user, TenantInvoice $invoice): bool
    {
        return $this->tenantInvoices(
            TenantInvoice::query()->whereKey($invoice),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<TenantPayment>  $query
     * @return Builder<TenantPayment>
     */
    public function tenantPayments(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $payments) use ($user): void {
            $payments
                ->whereIn(
                    'tenant_financial_account_id',
                    $this->tenantFinancialAccounts(
                        TenantFinancialAccount::query(),
                        $user,
                    )->select('id'),
                )
                ->orWhereIn(
                    'lease_contract_id',
                    $this->contracts(Contract::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'tenant_invoice_id',
                    $this->tenantInvoices(TenantInvoice::query(), $user)->select('id'),
                )
                ->orWhereIn(
                    'source_lease_payment_id',
                    $this->leasePayments(LeasePayment::query(), $user)->select('id'),
                );
        });
    }

    public function ownsTenantPayment(User $user, TenantPayment $payment): bool
    {
        return $this->tenantPayments(
            TenantPayment::query()->whereKey($payment),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function payments(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'contract_id',
            $this->contracts(Contract::query(), $user)->select('id'),
        );
    }

    public function ownsPayment(User $user, Payment $payment): bool
    {
        return $this->payments(Payment::query()->whereKey($payment), $user)->exists();
    }

    /**
     * @param  Builder<CommunicationReceipt>  $query
     * @return Builder<CommunicationReceipt>
     */
    public function communicationReceipts(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'communication_log_id',
            $this->communicationLogs(
                CommunicationLog::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsCommunicationReceipt(
        User $user,
        CommunicationReceipt $receipt,
    ): bool {
        return $this->communicationReceipts(
            CommunicationReceipt::query()->whereKey($receipt),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<NotificationTemplate>  $query
     * @return Builder<NotificationTemplate>
     */
    public function notificationTemplates(
        Builder $query,
        User $user,
        bool $includeSystem = true,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where(function (Builder $templates) use ($includeSystem, $user): void {
                $templates
                    ->where('municipality_id', $user->municipality_id)
                    ->orWhereHas('program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id))
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));

                if ($includeSystem) {
                    $templates->orWhere(function (Builder $system): void {
                        $system
                            ->whereNull('municipality_id')
                            ->whereNull('program_id')
                            ->whereNull('contest_id');
                    });
                }
            })
            ->where(function (Builder $templates) use ($user): void {
                $templates
                    ->whereNull('municipality_id')
                    ->orWhere('municipality_id', $user->municipality_id);
            })
            ->where(function (Builder $templates) use ($user): void {
                $templates
                    ->whereNull('program_id')
                    ->orWhereHas('program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $templates) use ($user): void {
                $templates
                    ->whereNull('contest_id')
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            });
    }

    public function ownsNotificationTemplate(
        User $user,
        NotificationTemplate $template,
        bool $includeSystem = true,
    ): bool {
        return $this->notificationTemplates(
            NotificationTemplate::query()->whereKey($template),
            $user,
            $includeSystem,
        )->exists();
    }

    public function canMutateNotificationTemplate(
        User $user,
        NotificationTemplate $template,
    ): bool {
        if ($this->platformScope->hasGlobalScope($user)) {
            return true;
        }

        return $user->municipality_id !== null
            && ! (
                $template->municipality_id === null
                && $template->program_id === null
                && $template->contest_id === null
            )
            && $this->ownsNotificationTemplate($user, $template, false);
    }

    public function ownsNotificationTemplateVersion(
        User $user,
        NotificationTemplateVersion $version,
        bool $includeSystem = true,
    ): bool {
        return NotificationTemplateVersion::query()
            ->whereKey($version)
            ->whereIn(
                'notification_template_id',
                $this->notificationTemplates(
                    NotificationTemplate::query(),
                    $user,
                    $includeSystem,
                )->select('id'),
            )
            ->exists();
    }

    public function canMutateNotificationTemplateVersion(
        User $user,
        NotificationTemplateVersion $version,
    ): bool {
        $version->loadMissing('template');

        return $version->template instanceof NotificationTemplate
            && $this->canMutateNotificationTemplate(
                $user,
                $version->template,
            );
    }

    /**
     * @param  Builder<NotificationEventRule>  $query
     * @return Builder<NotificationEventRule>
     */
    public function notificationEventRules(
        Builder $query,
        User $user,
        bool $includeSystem = true,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereIn(
                'notification_template_id',
                $this->notificationTemplates(
                    NotificationTemplate::query(),
                    $user,
                    $includeSystem,
                )->select('id'),
            )
            ->where(function (Builder $rules) use ($includeSystem, $user): void {
                $rules
                    ->where('municipality_id', $user->municipality_id)
                    ->orWhereHas('program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id))
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));

                if ($includeSystem) {
                    $rules->orWhere(function (Builder $system): void {
                        $system
                            ->whereNull('municipality_id')
                            ->whereNull('program_id')
                            ->whereNull('contest_id');
                    });
                }
            })
            ->where(function (Builder $rules) use ($user): void {
                $rules
                    ->whereNull('municipality_id')
                    ->orWhere('municipality_id', $user->municipality_id);
            })
            ->where(function (Builder $rules) use ($user): void {
                $rules
                    ->whereNull('program_id')
                    ->orWhereHas('program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $rules) use ($user): void {
                $rules
                    ->whereNull('contest_id')
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            });
    }

    public function ownsNotificationEventRule(
        User $user,
        NotificationEventRule $rule,
        bool $includeSystem = true,
    ): bool {
        return $this->notificationEventRules(
            NotificationEventRule::query()->whereKey($rule),
            $user,
            $includeSystem,
        )->exists();
    }

    public function canMutateNotificationEventRule(
        User $user,
        NotificationEventRule $rule,
    ): bool {
        if ($this->platformScope->hasGlobalScope($user)) {
            return true;
        }

        return $user->municipality_id !== null
            && ! (
                $rule->municipality_id === null
                && $rule->program_id === null
                && $rule->contest_id === null
            )
            && $this->ownsNotificationEventRule($user, $rule, false);
    }

    /**
     * @param  Builder<CommunicationLog>  $query
     * @return Builder<CommunicationLog>
     */
    public function communicationLogs(
        Builder $query,
        User $user,
    ): Builder {
        $query->whereNotNull('municipality_id');

        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('municipality_id', $user->municipality_id)
            ->where(function (Builder $logs) use ($user): void {
                $logs
                    ->whereNull('recipient_user_id')
                    ->orWhereHas('recipient', fn (Builder $recipient): Builder => $recipient
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $logs) use ($user): void {
                $logs
                    ->whereNull('notification_template_id')
                    ->orWhereIn(
                        'notification_template_id',
                        $this->notificationTemplates(
                            NotificationTemplate::query(),
                            $user,
                        )->select('id'),
                    );
            })
            ->where(function (Builder $logs) use ($user): void {
                $logs
                    ->whereNull('created_by')
                    ->orWhereHas('creator', fn (Builder $creator): Builder => $creator
                        ->where('municipality_id', $user->municipality_id));
            });
    }

    public function ownsCommunicationLog(
        User $user,
        CommunicationLog $communication,
    ): bool {
        return $this->communicationLogs(
            CommunicationLog::query()->whereKey($communication),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<CommunicationDelivery>  $query
     * @return Builder<CommunicationDelivery>
     */
    public function communicationDeliveries(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'communication_log_id',
            $this->communicationLogs(
                CommunicationLog::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsCommunicationDelivery(
        User $user,
        CommunicationDelivery $delivery,
    ): bool {
        return $this->communicationDeliveries(
            CommunicationDelivery::query()->whereKey($delivery),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<NotificationPreference>  $query
     * @return Builder<NotificationPreference>
     */
    public function notificationPreferences(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'user_id',
            $this->users(User::query(), $user)->select('id'),
        );
    }

    /**
     * @param  Builder<OfficialNotification>  $query
     * @return Builder<OfficialNotification>
     */
    public function officialNotifications(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('user', fn (Builder $recipient): Builder => $recipient
                ->where('municipality_id', $user->municipality_id))
            ->where(function (Builder $notifications) use ($user): void {
                $notifications
                    ->whereNull('application_id')
                    ->orWhereIn(
                        'application_id',
                        $this->applications(
                            Application::query(),
                            $user,
                        )->select('id'),
                    );
            })
            ->where(function (Builder $notifications) use ($user): void {
                $notifications
                    ->whereNull('communication_log_id')
                    ->orWhereIn(
                        'communication_log_id',
                        $this->communicationLogs(
                            CommunicationLog::query(),
                            $user,
                        )->select('id'),
                    );
            });
    }

    public function ownsOfficialNotification(
        User $user,
        OfficialNotification $notification,
    ): bool {
        return $this->officialNotifications(
            OfficialNotification::query()->whereKey($notification),
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
     * @param  Builder<EligibilityRuleSet>  $query
     * @return Builder<EligibilityRuleSet>
     */
    public function eligibilityRuleSets(Builder $query, User $user): Builder
    {
        return $this->ruleSetsThroughProgramOrContest($query, $user);
    }

    public function ownsEligibilityRuleSet(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return $this->eligibilityRuleSets(
            EligibilityRuleSet::query()->whereKey($ruleSet),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<EligibilityCriterion>  $query
     * @return Builder<EligibilityCriterion>
     */
    public function eligibilityCriteria(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'eligibility_rule_set_id',
            $this->eligibilityRuleSets(EligibilityRuleSet::query(), $user)->select('id'),
        );
    }

    public function ownsEligibilityCriterion(
        User $user,
        EligibilityCriterion $criterion,
    ): bool {
        return $this->eligibilityCriteria(
            EligibilityCriterion::query()->whereKey($criterion),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ScoringRuleSet>  $query
     * @return Builder<ScoringRuleSet>
     */
    public function scoringRuleSets(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $ruleSets) use ($user): void {
            $ruleSets
                ->whereHas(
                    'program',
                    fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id),
                )
                ->orWhereHas(
                    'contest.program',
                    fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id),
                );
        });
    }

    public function ownsScoringRuleSet(User $user, ScoringRuleSet $ruleSet): bool
    {
        return $this->scoringRuleSets(
            ScoringRuleSet::query()->whereKey($ruleSet),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ScoringCriterion>  $query
     * @return Builder<ScoringCriterion>
     */
    public function scoringCriteria(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'scoring_rule_set_id',
            $this->scoringRuleSets(ScoringRuleSet::query(), $user)->select('id'),
        );
    }

    public function ownsScoringCriterion(User $user, ScoringCriterion $criterion): bool
    {
        return $this->scoringCriteria(
            ScoringCriterion::query()->whereKey($criterion),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ScoringRule>  $query
     * @return Builder<ScoringRule>
     */
    public function scoringRules(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'scoring_criterion_id',
            $this->scoringCriteria(ScoringCriterion::query(), $user)->select('id'),
        );
    }

    public function ownsScoringRule(User $user, ScoringRule $rule): bool
    {
        return $this->scoringRules(ScoringRule::query()->whereKey($rule), $user)->exists();
    }

    /**
     * @param  Builder<TieBreakerRule>  $query
     * @return Builder<TieBreakerRule>
     */
    public function tieBreakerRules(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'scoring_rule_set_id',
            $this->scoringRuleSets(ScoringRuleSet::query(), $user)->select('id'),
        );
    }

    public function ownsTieBreakerRule(User $user, TieBreakerRule $rule): bool
    {
        return $this->tieBreakerRules(
            TieBreakerRule::query()->whereKey($rule),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ScoringRun>  $query
     * @return Builder<ScoringRun>
     */
    public function scoringRuns(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'scoring_rule_set_id',
            $this->scoringRuleSets(ScoringRuleSet::query(), $user)->select('id'),
        );
    }

    public function ownsScoringRun(User $user, ScoringRun $run): bool
    {
        return $this->scoringRuns(ScoringRun::query()->whereKey($run), $user)->exists();
    }

    /**
     * @param  Builder<ApplicationScore>  $query
     * @return Builder<ApplicationScore>
     */
    public function applicationScores(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'application_id',
            $this->applications(Application::query(), $user)->select('id'),
        );
    }

    public function ownsApplicationScore(User $user, ApplicationScore $score): bool
    {
        return $this->applicationScores(
            ApplicationScore::query()->whereKey($score),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RankingSnapshot>  $query
     * @return Builder<RankingSnapshot>
     */
    public function rankingSnapshots(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'scoring_run_id',
            $this->scoringRuns(ScoringRun::query(), $user)->select('id'),
        );
    }

    public function ownsRankingSnapshot(User $user, RankingSnapshot $snapshot): bool
    {
        return $this->rankingSnapshots(
            RankingSnapshot::query()->whereKey($snapshot),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<LotteryDraw>  $query
     * @return Builder<LotteryDraw>
     */
    public function lotteryDraws(Builder $query, User $user): Builder
    {
        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $draws) use ($user): void {
            $draws
                ->whereHas(
                    'program',
                    fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id),
                )
                ->orWhereHas(
                    'contest.program',
                    fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id),
                )
                ->orWhereHas(
                    'definitiveList.program',
                    fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id),
                );
        });
    }

    public function ownsLotteryDraw(User $user, LotteryDraw $draw): bool
    {
        return $this->lotteryDraws(LotteryDraw::query()->whereKey($draw), $user)->exists();
    }

    /**
     * @param  Builder<LotteryResult>  $query
     * @return Builder<LotteryResult>
     */
    public function lotteryResults(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'lottery_run_id',
            $this->lotteryDraws(LotteryDraw::query(), $user)->select('id'),
        );
    }

    public function ownsLotteryResult(User $user, LotteryResult $result): bool
    {
        return $this->lotteryResults(
            LotteryResult::query()->whereKey($result),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<DrawConvocation>  $query
     * @return Builder<DrawConvocation>
     */
    public function drawConvocations(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'lottery_run_id',
            $this->lotteryDraws(LotteryDraw::query(), $user)->select('id'),
        );
    }

    public function ownsDrawConvocation(User $user, DrawConvocation $convocation): bool
    {
        return $this->drawConvocations(
            DrawConvocation::query()->whereKey($convocation),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<PostDrawReport>  $query
     * @return Builder<PostDrawReport>
     */
    public function postDrawReports(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'lottery_run_id',
            $this->lotteryDraws(LotteryDraw::query(), $user)->select('id'),
        );
    }

    public function ownsPostDrawReport(User $user, PostDrawReport $report): bool
    {
        return $this->postDrawReports(
            PostDrawReport::query()->whereKey($report),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ControlledWithdrawal>  $query
     * @return Builder<ControlledWithdrawal>
     */
    public function controlledWithdrawals(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'application_id',
            $this->applications(Application::query(), $user)->select('id'),
        );
    }

    public function ownsControlledWithdrawal(User $user, ControlledWithdrawal $withdrawal): bool
    {
        return $this->controlledWithdrawals(
            ControlledWithdrawal::query()->whereKey($withdrawal),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ContestClosure>  $query
     * @return Builder<ContestClosure>
     */
    public function contestClosures(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'contest_id',
            $this->contests(Contest::query(), $user)->select('id'),
        );
    }

    public function ownsContestClosure(User $user, ContestClosure $closure): bool
    {
        return $this->contestClosures(
            ContestClosure::query()->whereKey($closure),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<RankingUpdateRun>  $query
     * @return Builder<RankingUpdateRun>
     */
    public function rankingUpdateRuns(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'lottery_run_id',
            $this->lotteryDraws(LotteryDraw::query(), $user)->select('id'),
        );
    }

    public function ownsRankingUpdateRun(User $user, RankingUpdateRun $run): bool
    {
        return $this->rankingUpdateRuns(
            RankingUpdateRun::query()->whereKey($run),
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
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'user',
            fn (Builder $owner): Builder => $owner
                ->where('municipality_id', $user->municipality_id),
        );
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
        $query->whereIn(
            'report_run_id',
            $this->reportRuns(ReportRun::query(), $user)->select('id'),
        );

        if (! $includeApplicationReports) {
            $query->whereHas(
                'run.definition',
                fn (Builder $definition): Builder => $definition
                    ->whereNotIn('code', self::APPLICATION_REPORT_CODES),
            );
        }

        return $query;
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
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'user',
            fn (Builder $owner): Builder => $owner
                ->where('municipality_id', $user->municipality_id),
        );
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
     * @param  Builder<MaintenanceRequest>  $query
     * @return Builder<MaintenanceRequest>
     */
    public function maintenanceRequests(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'housing_unit_id',
            $this->housingUnits(
                HousingUnit::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsMaintenanceRequest(
        User $user,
        MaintenanceRequest $request,
    ): bool {
        return $this->maintenanceRequests(
            MaintenanceRequest::query()->whereKey($request),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<MaintenanceAssignment>  $query
     * @return Builder<MaintenanceAssignment>
     */
    public function maintenanceAssignments(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'maintenance_request_id',
            $this->maintenanceRequests(
                MaintenanceRequest::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsMaintenanceAssignment(
        User $user,
        MaintenanceAssignment $assignment,
    ): bool {
        return $this->maintenanceAssignments(
            MaintenanceAssignment::query()
                ->whereKey($assignment),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<MaintenanceIntervention>  $query
     * @return Builder<MaintenanceIntervention>
     */
    public function maintenanceInterventions(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'maintenance_request_id',
            $this->maintenanceRequests(
                MaintenanceRequest::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsMaintenanceIntervention(
        User $user,
        MaintenanceIntervention $intervention,
    ): bool {
        return $this->maintenanceInterventions(
            MaintenanceIntervention::query()
                ->whereKey($intervention),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<MaintenanceCost>  $query
     * @return Builder<MaintenanceCost>
     */
    public function maintenanceCosts(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'maintenance_request_id',
            $this->maintenanceRequests(
                MaintenanceRequest::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsMaintenanceCost(
        User $user,
        MaintenanceCost $cost,
    ): bool {
        return $this->maintenanceCosts(
            MaintenanceCost::query()->whereKey($cost),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<PropertyInspection>  $query
     * @return Builder<PropertyInspection>
     */
    public function propertyInspections(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'housing_unit_id',
            $this->housingUnits(
                HousingUnit::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsPropertyInspection(
        User $user,
        PropertyInspection $inspection,
    ): bool {
        return $this->propertyInspections(
            PropertyInspection::query()
                ->whereKey($inspection),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<VisitAvailability>  $query
     * @return Builder<VisitAvailability>
     */
    public function visitAvailabilities(
        Builder $query,
        User $user,
    ): Builder {
        return $this->structurallyValidVisitAvailabilities(
            $this->canonicalMunicipalScope(
                $query,
                $user,
                'visit_availabilities',
            ),
        );
    }

    /**
     * @param  Builder<VisitAvailability>  $query
     * @return Builder<VisitAvailability>
     */
    public function structurallyValidVisitAvailabilities(
        Builder $query,
    ): Builder {
        return $query
            ->whereNotNull(
                'visit_availabilities.municipality_id',
            )
            ->where(function (Builder $origins): void {
                $origins
                    ->whereNotNull('contest_id')
                    ->orWhereNotNull('housing_unit_id');
            })
            ->where(function (Builder $contestScope): void {
                $contestScope
                    ->whereNull('contest_id')
                    ->orWhereHas(
                        'contest.program',
                        fn (Builder $program): Builder => $program
                            ->whereColumn(
                                'programs.municipality_id',
                                'visit_availabilities.municipality_id',
                            ),
                    );
            })
            ->where(function (Builder $housingScope): void {
                $housingScope
                    ->whereNull('housing_unit_id')
                    ->orWhereHas(
                        'housingUnit',
                        fn (Builder $housingUnit): Builder => $housingUnit
                            ->whereNotNull('municipality_id')
                            ->whereColumn(
                                'housing_units.municipality_id',
                                'visit_availabilities.municipality_id',
                            ),
                    );
            })
            ->where(function (Builder $staffScope): void {
                $staffScope
                    ->whereNull('staff_user_id')
                    ->orWhereHas(
                        'staff',
                        fn (Builder $staff): Builder => $staff
                            ->whereNotNull('municipality_id')
                            ->whereColumn(
                                'users.municipality_id',
                                'visit_availabilities.municipality_id',
                            ),
                    );
            });
    }

    public function ownsVisitAvailability(
        User $user,
        VisitAvailability $availability,
    ): bool {
        return $this->visitAvailabilities(
            VisitAvailability::query()->whereKey($availability),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<VisitSlot>  $query
     * @return Builder<VisitSlot>
     */
    public function visitSlots(
        Builder $query,
        User $user,
    ): Builder {
        return $this->structurallyValidVisitSlots(
            $this->canonicalMunicipalScope(
                $query,
                $user,
                'visit_slots',
            ),
        );
    }

    /**
     * @param  Builder<VisitSlot>  $query
     * @return Builder<VisitSlot>
     */
    public function structurallyValidVisitSlots(
        Builder $query,
    ): Builder {
        $availabilityIds = $this
            ->structurallyValidVisitAvailabilities(
                VisitAvailability::query(),
            )
            ->whereColumn(
                'visit_availabilities.municipality_id',
                'visit_slots.municipality_id',
            )
            ->where(function (Builder $ids): void {
                $this->whereColumnsEqualOrBothNull(
                    $ids,
                    'visit_availabilities.contest_id',
                    'visit_slots.contest_id',
                );
            })
            ->where(function (Builder $ids): void {
                $this->whereColumnsEqualOrBothNull(
                    $ids,
                    'visit_availabilities.housing_unit_id',
                    'visit_slots.housing_unit_id',
                );
            })
            ->where(function (Builder $ids): void {
                $this->whereColumnsEqualOrBothNull(
                    $ids,
                    'visit_availabilities.staff_user_id',
                    'visit_slots.staff_user_id',
                );
            })
            ->select('visit_availabilities.id');

        return $query
            ->whereNotNull('visit_slots.municipality_id')
            ->whereNotNull('visit_availability_id')
            ->whereIn('visit_availability_id', $availabilityIds)
            ->where(function (Builder $contestScope): void {
                $contestScope
                    ->whereNull('contest_id')
                    ->orWhereHas(
                        'contest.program',
                        fn (Builder $program): Builder => $program
                            ->whereColumn(
                                'programs.municipality_id',
                                'visit_slots.municipality_id',
                            ),
                    );
            })
            ->where(function (Builder $housingScope): void {
                $housingScope
                    ->whereNull('housing_unit_id')
                    ->orWhereHas(
                        'housingUnit',
                        fn (Builder $housingUnit): Builder => $housingUnit
                            ->whereNotNull('municipality_id')
                            ->whereColumn(
                                'housing_units.municipality_id',
                                'visit_slots.municipality_id',
                            ),
                    );
            })
            ->where(function (Builder $staffScope): void {
                $staffScope
                    ->whereNull('staff_user_id')
                    ->orWhereHas(
                        'staff',
                        fn (Builder $staff): Builder => $staff
                            ->whereNotNull('municipality_id')
                            ->whereColumn(
                                'users.municipality_id',
                                'visit_slots.municipality_id',
                            ),
                    );
            });
    }

    public function ownsVisitSlot(
        User $user,
        VisitSlot $slot,
    ): bool {
        return $this->visitSlots(
            VisitSlot::query()->whereKey($slot),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<HousingVisit>  $query
     * @return Builder<HousingVisit>
     */
    public function housingVisits(
        Builder $query,
        User $user,
    ): Builder {
        return $this->structurallyValidHousingVisits(
            $this->canonicalMunicipalScope(
                $query,
                $user,
                'housing_visits',
            ),
        );
    }

    /**
     * @param  Builder<HousingVisit>  $query
     * @return Builder<HousingVisit>
     */
    public function structurallyValidHousingVisits(
        Builder $query,
    ): Builder {
        $slotIds = $this
            ->structurallyValidVisitSlots(
                VisitSlot::query(),
            )
            ->whereColumn(
                'visit_slots.municipality_id',
                'housing_visits.municipality_id',
            )
            ->where(function (Builder $ids): void {
                $this->whereColumnsEqualOrBothNull(
                    $ids,
                    'visit_slots.contest_id',
                    'housing_visits.contest_id',
                );
            })
            ->where(function (Builder $ids): void {
                $this->whereColumnsEqualOrBothNull(
                    $ids,
                    'visit_slots.housing_unit_id',
                    'housing_visits.housing_unit_id',
                );
            })
            ->select('visit_slots.id');

        $applicationIds = Application::query()
            ->select('applications.id')
            ->join(
                'programs as visit_application_programs',
                'visit_application_programs.id',
                '=',
                'applications.program_id',
            )
            ->join(
                'contests as visit_application_contests',
                'visit_application_contests.id',
                '=',
                'applications.contest_id',
            )
            ->join(
                'programs as visit_contest_programs',
                'visit_contest_programs.id',
                '=',
                'visit_application_contests.program_id',
            )
            ->whereColumn(
                'applications.program_id',
                'visit_application_contests.program_id',
            )
            ->whereColumn(
                'visit_application_programs.municipality_id',
                'housing_visits.municipality_id',
            )
            ->whereColumn(
                'visit_contest_programs.municipality_id',
                'housing_visits.municipality_id',
            )
            ->whereColumn(
                'applications.contest_id',
                'housing_visits.contest_id',
            )
            ->whereColumn(
                'applications.user_id',
                'housing_visits.candidate_user_id',
            )
            ->whereNull('visit_application_programs.deleted_at')
            ->whereNull('visit_application_contests.deleted_at')
            ->whereNull('visit_contest_programs.deleted_at');

        return $query
            ->whereNotNull('housing_visits.municipality_id')
            ->whereNotNull('visit_slot_id')
            ->whereIn('visit_slot_id', $slotIds)
            ->where(function (Builder $applicationScope) use (
                $applicationIds,
            ): void {
                $applicationScope
                    ->whereNull('application_id')
                    ->orWhereIn('application_id', $applicationIds);
            })
            ->where(function (Builder $contestScope): void {
                $contestScope
                    ->whereNull('contest_id')
                    ->orWhereHas(
                        'contest.program',
                        fn (Builder $program): Builder => $program
                            ->whereColumn(
                                'programs.municipality_id',
                                'housing_visits.municipality_id',
                            ),
                    );
            })
            ->where(function (Builder $housingScope): void {
                $housingScope
                    ->whereNull('housing_unit_id')
                    ->orWhereHas(
                        'housingUnit',
                        fn (Builder $housingUnit): Builder => $housingUnit
                            ->whereNotNull('municipality_id')
                            ->whereColumn(
                                'housing_units.municipality_id',
                                'housing_visits.municipality_id',
                            ),
                    );
            })
            ->where(function (Builder $staffScope): void {
                $staffScope
                    ->whereNull('staff_user_id')
                    ->orWhereHas(
                        'staff',
                        fn (Builder $staff): Builder => $staff
                            ->whereNotNull('municipality_id')
                            ->whereColumn(
                                'users.municipality_id',
                                'housing_visits.municipality_id',
                            ),
                    );
            });
    }

    public function isStructurallyValidHousingVisit(
        HousingVisit $visit,
    ): bool {
        return $this->structurallyValidHousingVisits(
            HousingVisit::query()->whereKey($visit),
        )->exists();
    }

    public function ownsHousingVisit(
        User $user,
        HousingVisit $visit,
    ): bool {
        return $this->housingVisits(
            HousingVisit::query()->whereKey($visit),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<InternalAlert>  $query
     * @return Builder<InternalAlert>
     */
    public function internalAlerts(
        Builder $query,
        User $user,
    ): Builder {
        $query->where(function (Builder $alerts): void {
            $alerts
                ->whereNotNull('municipality_id')
                ->orWhereNotNull('program_id')
                ->orWhereNotNull('contest_id')
                ->orWhereNotNull('application_id');
        });

        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where(function (Builder $alerts) use ($user): void {
                $alerts
                    ->whereNull('municipality_id')
                    ->orWhere('municipality_id', $user->municipality_id);
            })
            ->where(function (Builder $alerts) use ($user): void {
                $alerts
                    ->whereNull('program_id')
                    ->orWhereHas('program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $alerts) use ($user): void {
                $alerts
                    ->whereNull('contest_id')
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $alerts) use ($user): void {
                $alerts
                    ->whereNull('application_id')
                    ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            });
    }

    public function ownsInternalAlert(
        User $user,
        InternalAlert $alert,
    ): bool {
        return $this->internalAlerts(
            InternalAlert::query()->whereKey($alert),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<AdministrativeWorkflowConfig>  $query
     * @return Builder<AdministrativeWorkflowConfig>
     */
    public function administrativeWorkflowConfigs(
        Builder $query,
        User $user,
    ): Builder {
        $query->where(function (Builder $configs): void {
            $configs
                ->whereNotNull('program_id')
                ->orWhereNotNull('contest_id');
        });

        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where(function (Builder $configs) use ($user): void {
                $configs
                    ->whereNull('program_id')
                    ->orWhereHas('program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $configs) use ($user): void {
                $configs
                    ->whereNull('contest_id')
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            });
    }

    public function ownsAdministrativeWorkflowConfig(
        User $user,
        AdministrativeWorkflowConfig $config,
    ): bool {
        return $this->administrativeWorkflowConfigs(
            AdministrativeWorkflowConfig::query()->whereKey($config),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<ContextualFaq>  $query
     * @return Builder<ContextualFaq>
     */
    public function contextualFaqs(
        Builder $query,
        User $user,
        bool $includeSystem = true,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $faqs) use ($includeSystem, $user): void {
            $faqs->whereHas('contest.program', fn (Builder $program): Builder => $program
                ->where('municipality_id', $user->municipality_id));

            if ($includeSystem) {
                $faqs->orWhereNull('contest_id');
            }
        });
    }

    public function ownsContextualFaq(
        User $user,
        ContextualFaq $faq,
        bool $includeSystem = true,
    ): bool {
        return $this->contextualFaqs(
            ContextualFaq::query()->whereKey($faq),
            $user,
            $includeSystem,
        )->exists();
    }

    public function canMutateContextualFaq(
        User $user,
        ContextualFaq $faq,
    ): bool {
        if ($faq->contest_id === null) {
            return $this->platformScope->hasGlobalScope($user);
        }

        return $this->ownsContextualFaq($user, $faq, false);
    }

    /**
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function supportTickets(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('user', fn (Builder $owner): Builder => $owner
                ->where('municipality_id', $user->municipality_id))
            ->where(function (Builder $tickets) use ($user): void {
                $tickets
                    ->whereNull('application_id')
                    ->orWhereHas('application.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $tickets) use ($user): void {
                $tickets
                    ->whereNull('contest_id')
                    ->orWhereHas('contest.program', fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id));
            })
            ->where(function (Builder $tickets) use ($user): void {
                $tickets
                    ->whereNull('housing_unit_id')
                    ->orWhereHas('housingUnit', fn (Builder $unit): Builder => $unit
                        ->where('municipality_id', $user->municipality_id));
            });
    }

    public function ownsSupportTicket(
        User $user,
        SupportTicket $ticket,
    ): bool {
        return $this->supportTickets(
            SupportTicket::query()->whereKey($ticket),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<SupportTicketAttachment>  $query
     * @return Builder<SupportTicketAttachment>
     */
    public function supportTicketAttachments(
        Builder $query,
        User $user,
    ): Builder {
        return $query->whereIn(
            'support_ticket_id',
            $this->supportTickets(
                SupportTicket::query(),
                $user,
            )->select('id'),
        );
    }

    public function ownsSupportTicketAttachment(
        User $user,
        SupportTicketAttachment $attachment,
    ): bool {
        return $this->supportTicketAttachments(
            SupportTicketAttachment::query()->whereKey($attachment),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<WorkTask>  $query
     * @return Builder<WorkTask>
     */
    public function workTasks(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $tasks) use ($user): void {
            $tasks
                ->whereHas('municipalTeam', fn (Builder $team): Builder => $team
                    ->where('municipality_id', $user->municipality_id))
                ->orWhere(function (Builder $withoutTeam) use ($user): void {
                    $withoutTeam
                        ->whereNull('municipal_team_id')
                        ->whereHas('assignedUser', fn (Builder $assignee): Builder => $assignee
                            ->where('municipality_id', $user->municipality_id));
                })
                ->orWhere(function (Builder $unassigned) use ($user): void {
                    $unassigned
                        ->whereNull('municipal_team_id')
                        ->whereNull('assigned_user_id')
                        ->whereHas('createdBy', fn (Builder $creator): Builder => $creator
                            ->where('municipality_id', $user->municipality_id));
                });
        });
    }

    public function ownsWorkTask(
        User $user,
        WorkTask $task,
    ): bool {
        return $this->workTasks(
            WorkTask::query()->whereKey($task),
            $user,
        )->exists();
    }

    /**
     * @param  Builder<MaintenanceCategory>  $query
     * @return Builder<MaintenanceCategory>
     */
    public function maintenanceCategories(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query->where(
                function (Builder $catalog): void {
                    $catalog
                        ->where(
                            function (Builder $system): void {
                                $system
                                    ->where('is_system', true)
                                    ->whereNull('municipality_id');
                            },
                        )
                        ->orWhere(
                            function (Builder $municipal): void {
                                $municipal
                                    ->where('is_system', false)
                                    ->whereNotNull('municipality_id');
                            },
                        );
                },
            );
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $this->maintenanceCategoriesForMunicipality(
            $query,
            (int) $user->municipality_id,
        );
    }

    /**
     * @param  Builder<MaintenanceCategory>  $query
     * @return Builder<MaintenanceCategory>
     */
    public function maintenanceCategoriesForMunicipality(
        Builder $query,
        int $municipalityId,
    ): Builder {
        return $query->where(
            function (Builder $catalog) use (
                $municipalityId,
            ): void {
                $catalog
                    ->where(
                        function (Builder $system): void {
                            $system
                                ->where('is_system', true)
                                ->whereNull('municipality_id');
                        },
                    )
                    ->orWhere(
                        function (Builder $municipal) use (
                            $municipalityId,
                        ): void {
                            $municipal
                                ->where('is_system', false)
                                ->where(
                                    'municipality_id',
                                    $municipalityId,
                                );
                        },
                    );
            },
        );
    }

    public function ownsMaintenanceCategory(
        User $user,
        MaintenanceCategory $category,
    ): bool {
        return $this->maintenanceCategories(
            MaintenanceCategory::query()
                ->whereKey($category),
            $user,
        )->exists();
    }

    public function canMutateMaintenanceCategory(
        User $user,
        MaintenanceCategory $category,
    ): bool {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $this->maintenanceCategories(
                MaintenanceCategory::query()
                    ->whereKey($category),
                $user,
            )->exists();
        }

        if ($user->municipality_id === null) {
            return false;
        }

        return MaintenanceCategory::query()
            ->whereKey($category)
            ->where('is_system', false)
            ->where(
                'municipality_id',
                $user->municipality_id,
            )
            ->exists();
    }

    /**
     * @param  Builder<MaintenanceSupplier>  $query
     * @return Builder<MaintenanceSupplier>
     */
    public function maintenanceSuppliers(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query->whereNotNull('municipality_id');
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $this->maintenanceSuppliersForMunicipality(
            $query,
            (int) $user->municipality_id,
        );
    }

    /**
     * @param  Builder<MaintenanceSupplier>  $query
     * @return Builder<MaintenanceSupplier>
     */
    public function maintenanceSuppliersForMunicipality(
        Builder $query,
        int $municipalityId,
    ): Builder {
        return $query->where(
            'municipality_id',
            $municipalityId,
        );
    }

    public function ownsMaintenanceSupplier(
        User $user,
        MaintenanceSupplier $supplier,
    ): bool {
        return $this->maintenanceSuppliers(
            MaintenanceSupplier::query()
                ->whereKey($supplier),
            $user,
        )->exists();
    }

    public function canMutateMaintenanceSupplier(
        User $user,
        MaintenanceSupplier $supplier,
    ): bool {
        return $this->ownsMaintenanceSupplier(
            $user,
            $supplier,
        );
    }

    /**
     * @param  Builder<InspectionChecklistTemplate>  $query
     * @return Builder<InspectionChecklistTemplate>
     */
    public function inspectionChecklistTemplates(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query->where(
                function (Builder $catalog): void {
                    $catalog
                        ->where(
                            function (Builder $system): void {
                                $system
                                    ->where('is_system', true)
                                    ->whereNull('municipality_id');
                            },
                        )
                        ->orWhere(
                            function (Builder $municipal): void {
                                $municipal
                                    ->where('is_system', false)
                                    ->whereNotNull('municipality_id');
                            },
                        );
                },
            );
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $this
            ->inspectionChecklistTemplatesForMunicipality(
                $query,
                (int) $user->municipality_id,
            );
    }

    /**
     * @param  Builder<InspectionChecklistTemplate>  $query
     * @return Builder<InspectionChecklistTemplate>
     */
    public function inspectionChecklistTemplatesForMunicipality(
        Builder $query,
        int $municipalityId,
    ): Builder {
        return $query->where(
            function (Builder $catalog) use (
                $municipalityId,
            ): void {
                $catalog
                    ->where(
                        function (Builder $system): void {
                            $system
                                ->where('is_system', true)
                                ->whereNull('municipality_id');
                        },
                    )
                    ->orWhere(
                        function (Builder $municipal) use (
                            $municipalityId,
                        ): void {
                            $municipal
                                ->where('is_system', false)
                                ->where(
                                    'municipality_id',
                                    $municipalityId,
                                );
                        },
                    );
            },
        );
    }

    public function ownsInspectionChecklistTemplate(
        User $user,
        InspectionChecklistTemplate $template,
    ): bool {
        return $this->inspectionChecklistTemplates(
            InspectionChecklistTemplate::query()
                ->whereKey($template),
            $user,
        )->exists();
    }

    public function canMutateInspectionChecklistTemplate(
        User $user,
        InspectionChecklistTemplate $template,
    ): bool {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $this->inspectionChecklistTemplates(
                InspectionChecklistTemplate::query()
                    ->whereKey($template),
                $user,
            )->exists();
        }

        if ($user->municipality_id === null) {
            return false;
        }

        return InspectionChecklistTemplate::query()
            ->whereKey($template)
            ->where('is_system', false)
            ->where(
                'municipality_id',
                $user->municipality_id,
            )
            ->exists();
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

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function ruleSetsThroughProgramOrContest(
        Builder $query,
        User $user,
    ): Builder {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $ruleSets) use ($user): void {
            $ruleSets
                ->whereHas(
                    'program',
                    fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id),
                )
                ->orWhereHas(
                    'contest.program',
                    fn (Builder $program): Builder => $program
                        ->where('municipality_id', $user->municipality_id),
                );
        });
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function canonicalMunicipalScope(
        Builder $query,
        User $user,
        string $table,
    ): Builder {
        $query->whereNotNull($table.'.municipality_id');

        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $table.'.municipality_id',
            $user->municipality_id,
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function whereColumnsEqualOrBothNull(
        Builder $query,
        string $first,
        string $second,
    ): void {
        $query
            ->whereColumn($first, $second)
            ->orWhere(function (Builder $nulls) use (
                $first,
                $second,
            ): void {
                $nulls
                    ->whereNull($first)
                    ->whereNull($second);
            });
    }
}
