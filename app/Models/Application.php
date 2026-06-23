<?php

namespace App\Models;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property ApplicationStatus $status
 * @property User $user
 * @property AdhesionRegistration $adhesionRegistration
 * @property Program $program
 * @property Contest $contest
 * @property Household|null $household
 * @property CurrentHousingSituation|null $currentHousingSituation
 * @property Carbon|null $submitted_at
 */
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'submitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
            'locked_at' => 'datetime',
            'declaration_accepted' => 'boolean',
            'declaration_accepted_at' => 'datetime',
            'contest_rules_accepted' => 'boolean',
            'contest_rules_accepted_at' => 'datetime',
            'data_processing_accepted' => 'boolean',
            'data_processing_accepted_at' => 'datetime',
            'truthfulness_accepted' => 'boolean',
            'truthfulness_accepted_at' => 'datetime',
            'data_current_confirmed' => 'boolean',
            'data_current_confirmed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<AdhesionRegistration, $this> */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<CurrentHousingSituation, $this> */
    public function currentHousingSituation(): BelongsTo
    {
        return $this->belongsTo(CurrentHousingSituation::class);
    }

    /** @return HasMany<ApplicationStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->latest();
    }

    /** @return HasMany<ApplicationSnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(ApplicationSnapshot::class);
    }

    /** @return HasMany<ApplicationDocument, $this> */
    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /** @return HasMany<ApplicationPreference, $this> */
    public function preferences(): HasMany
    {
        return $this->hasMany(ApplicationPreference::class)->orderBy('preference_order');
    }

    /** @return HasMany<HousingPreference, $this> */
    public function housingPreferences(): HasMany
    {
        return $this->hasMany(HousingPreference::class)->orderBy('preference_order');
    }

    /** @return HasMany<ApplicationDeclaration, $this> */
    public function declarations(): HasMany
    {
        return $this->hasMany(ApplicationDeclaration::class);
    }

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    /** @return HasMany<EligibilityCheck, $this> */
    public function eligibilityChecks(): HasMany
    {
        return $this->hasMany(EligibilityCheck::class);
    }

    /** @return HasOne<EligibilityCheck, $this> */
    public function latestEligibilityCheck(): HasOne
    {
        return $this->hasOne(EligibilityCheck::class)->latestOfMany();
    }

    /** @return HasOne<AdministrativeProcess, $this> */
    public function administrativeProcess(): HasOne
    {
        return $this->hasOne(AdministrativeProcess::class);
    }

    /** @return HasMany<ApplicationReview, $this> */
    public function applicationReviews(): HasMany
    {
        return $this->hasMany(ApplicationReview::class);
    }

    /** @return HasMany<CorrectionRequest, $this> */
    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /** @return HasMany<CorrectionResponse, $this> */
    public function correctionResponses(): HasMany
    {
        return $this->hasMany(CorrectionResponse::class);
    }

    /** @return HasMany<AdministrativeDecision, $this> */
    public function administrativeDecisions(): HasMany
    {
        return $this->hasMany(AdministrativeDecision::class);
    }

    /** @return HasMany<AdministrativeTask, $this> */
    public function administrativeTasks(): HasMany
    {
        return $this->hasMany(AdministrativeTask::class);
    }

    /** @return HasMany<ApplicationScore, $this> */
    public function applicationScores(): HasMany
    {
        return $this->hasMany(ApplicationScore::class);
    }

    /** @return HasMany<ProvisionalListEntry, $this> */
    public function provisionalListEntries(): HasMany
    {
        return $this->hasMany(ProvisionalListEntry::class);
    }

    /** @return HasMany<DefinitiveListEntry, $this> */
    public function definitiveListEntries(): HasMany
    {
        return $this->hasMany(DefinitiveListEntry::class);
    }

    /** @return HasMany<Complaint, $this> */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /** @return HasMany<ComplaintDecision, $this> */
    public function complaintDecisions(): HasMany
    {
        return $this->hasMany(ComplaintDecision::class);
    }

    /** @return HasMany<Hearing, $this> */
    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    /** @return HasMany<HearingSubmission, $this> */
    public function hearingSubmissions(): HasMany
    {
        return $this->hasMany(HearingSubmission::class);
    }

    /** @return HasMany<OfficialNotification, $this> */
    public function officialNotifications(): HasMany
    {
        return $this->hasMany(OfficialNotification::class);
    }

    /** @return HasMany<ProcessTimelineEvent, $this> */
    public function processTimelineEvents(): HasMany
    {
        return $this->hasMany(ProcessTimelineEvent::class)->latest('occurred_at');
    }

    /** @return HasOne<ApplicationPublicStatusSnapshot, $this> */
    public function publicStatusSnapshot(): HasOne
    {
        return $this->hasOne(ApplicationPublicStatusSnapshot::class);
    }

    /** @return HasMany<ProcessAction, $this> */
    public function processActions(): HasMany
    {
        return $this->hasMany(ProcessAction::class);
    }

    /** @return HasMany<AdditionalDocumentRequest, $this> */
    public function additionalDocumentRequests(): HasMany
    {
        return $this->hasMany(AdditionalDocumentRequest::class);
    }

    /** @return HasMany<AdditionalDocumentSubmission, $this> */
    public function additionalDocumentSubmissions(): HasMany
    {
        return $this->hasMany(AdditionalDocumentSubmission::class);
    }

    /** @return HasMany<ControlledWithdrawal, $this> */
    public function controlledWithdrawals(): HasMany
    {
        return $this->hasMany(ControlledWithdrawal::class);
    }

    /** @return HasMany<FutureApplicationDataReuse, $this> */
    public function futureDataReusesAsSource(): HasMany
    {
        return $this->hasMany(FutureApplicationDataReuse::class, 'source_application_id');
    }

    /** @return HasMany<FutureApplicationDataReuse, $this> */
    public function futureDataReusesAsTarget(): HasMany
    {
        return $this->hasMany(FutureApplicationDataReuse::class, 'target_application_id');
    }

    /** @return HasMany<ApplicationReport, $this> */
    public function applicationReports(): HasMany
    {
        return $this->hasMany(ApplicationReport::class);
    }

    /** @return HasMany<DocumentDossier, $this> */
    public function documentDossiers(): HasMany
    {
        return $this->hasMany(DocumentDossier::class);
    }

    /** @return HasMany<DocumentAiValidation, $this> */
    public function documentAiValidations(): HasMany
    {
        return $this->hasMany(DocumentAiValidation::class);
    }

    /** @return HasMany<DocumentAiValidationRun, $this> */
    public function documentAiValidationRuns(): HasMany
    {
        return $this->hasMany(DocumentAiValidationRun::class);
    }

    /** @return HasMany<DocumentAiScore, $this> */
    public function documentAiScores(): HasMany
    {
        return $this->hasMany(DocumentAiScore::class);
    }

    /** @return HasMany<DocumentAiSuggestion, $this> */
    public function documentAiSuggestions(): HasMany
    {
        return $this->hasMany(DocumentAiSuggestion::class);
    }

    /** @return HasMany<InternalAlert, $this> */
    public function internalAlerts(): HasMany
    {
        return $this->hasMany(InternalAlert::class);
    }

    /** @return HasMany<ProcedureMinute, $this> */
    public function procedureMinutes(): HasMany
    {
        return $this->hasMany(ProcedureMinute::class);
    }

    /** @return HasMany<ProcessConfirmation, $this> */
    public function processConfirmations(): HasMany
    {
        return $this->hasMany(ProcessConfirmation::class);
    }

    /** @return HasMany<Allocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /** @return HasMany<AllocationOffer, $this> */
    public function allocationOffers(): HasMany
    {
        return $this->hasMany(AllocationOffer::class);
    }

    /** @return HasMany<LotteryParticipant, $this> */
    public function lotteryParticipants(): HasMany
    {
        return $this->hasMany(LotteryParticipant::class);
    }

    /** @return HasMany<LotteryDrawResult, $this> */
    public function lotteryDrawResults(): HasMany
    {
        return $this->hasMany(LotteryDrawResult::class);
    }

    /** @return HasMany<ReserveListEntry, $this> */
    public function reserveListEntries(): HasMany
    {
        return $this->hasMany(ReserveListEntry::class);
    }

    /** @return HasMany<RentCalculation, $this> */
    public function rentCalculations(): HasMany
    {
        return $this->hasMany(RentCalculation::class);
    }

    /** @return HasMany<Contract, $this> */
    public function leaseContracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** @return HasMany<ContractDeposit, $this> */
    public function contractDeposits(): HasMany
    {
        return $this->hasMany(ContractDeposit::class);
    }

    /** @return HasOne<Allocation, $this> */
    public function activeAllocation(): HasOne
    {
        return $this->hasOne(Allocation::class)->active()->latestOfMany();
    }

    /** @return HasOne<Allocation, $this> */
    public function acceptedAllocation(): HasOne
    {
        return $this->hasOne(Allocation::class)->readyForContract()->latestOfMany();
    }

    /** @return HasOne<ApplicationScore, $this> */
    public function latestApplicationScore(): HasOne
    {
        return $this->hasOne(ApplicationScore::class)->latestOfMany();
    }

    /** @return HasMany<AdministrativeProcessNote, $this> */
    public function administrativeProcessNotes(): HasMany
    {
        return $this->hasMany(AdministrativeProcessNote::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<SimulationSession, $this>
     */
    public function simulationSessions(): HasMany
    {
        return $this->hasMany(SimulationSession::class);
    }

    /**
     * @return HasMany<ApplicationPrefill, $this>
     */
    public function applicationPrefills(): HasMany
    {
        return $this->hasMany(ApplicationPrefill::class);
    }

    /**
     * @return HasMany<HousingVisit, $this>
     */
    public function housingVisits(): HasMany
    {
        return $this->hasMany(HousingVisit::class);
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * @return HasMany<CandidateInteraction, $this>
     */
    public function candidateInteractions(): HasMany
    {
        return $this->hasMany(CandidateInteraction::class);
    }

    /**
     * @return HasMany<ApplicationSimulationInconsistency, $this>
     */
    public function simulationInconsistencies(): HasMany
    {
        return $this->hasMany(ApplicationSimulationInconsistency::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAdmittedForScoring(Builder $query): Builder
    {
        return $query->whereHas('administrativeProcess', function (Builder $builder) {
            $builder->where('status', AdministrativeProcessStatus::AdmittedForScoring->value);
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeReadyForAllocation(Builder $query): Builder
    {
        return $query->whereHas('definitiveListEntries', function (Builder $builder): void {
            /** @var Builder<DefinitiveListEntry> $builder */
            $builder->eligibleForAllocation();
        })
            ->whereNotIn('status', [
                ApplicationStatus::Draft->value,
                ApplicationStatus::Withdrawn->value,
                ApplicationStatus::Cancelled->value,
                ApplicationStatus::Expired->value,
                ApplicationStatus::Excluded->value,
            ]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeReadyForContract(Builder $query): Builder
    {
        return $query->whereHas('allocations', function (Builder $builder): void {
            /** @var Builder<Allocation> $builder */
            $builder->readyForContract();
        });
    }

    public function isEditable(): bool
    {
        return $this->status === ApplicationStatus::Draft;
    }
}
