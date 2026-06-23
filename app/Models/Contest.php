<?php

namespace App\Models;

use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use Database\Factories\ContestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property ContestStatus $status
 * @property Carbon|null $opens_at
 * @property Carbon|null $closes_at
 * @property Carbon|null $published_at
 */
class Contest extends Model
{
    /** @use HasFactory<ContestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'created_by',
        'updated_by',
        'code',
        'slug',
        'title',
        'summary',
        'description',
        'application_instructions',
        'status',
        'opens_at',
        'closes_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContestStatus::class,
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<ContestDeadline, $this> */
    public function deadlines(): HasMany
    {
        return $this->hasMany(ContestDeadline::class)->orderBy('sort_order');
    }

    /** @return HasMany<ContestJuryMember, $this> */
    public function juryMembers(): HasMany
    {
        return $this->hasMany(ContestJuryMember::class);
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** @return HasMany<EligibilityRuleSet, $this> */
    public function eligibilityRuleSets(): HasMany
    {
        return $this->hasMany(EligibilityRuleSet::class);
    }

    /** @return HasMany<EligibilityCheck, $this> */
    public function eligibilityChecks(): HasMany
    {
        return $this->hasMany(EligibilityCheck::class);
    }

    /** @return HasMany<AdministrativeProcess, $this> */
    public function administrativeProcesses(): HasMany
    {
        return $this->hasMany(AdministrativeProcess::class);
    }

    /** @return HasMany<AdministrativeWorkflowConfig, $this> */
    public function administrativeWorkflowConfigs(): HasMany
    {
        return $this->hasMany(AdministrativeWorkflowConfig::class);
    }

    /** @return HasMany<ScoringRuleSet, $this> */
    public function scoringRuleSets(): HasMany
    {
        return $this->hasMany(ScoringRuleSet::class);
    }

    /** @return HasMany<ScoringRun, $this> */
    public function scoringRuns(): HasMany
    {
        return $this->hasMany(ScoringRun::class);
    }

    /** @return HasMany<ApplicationScore, $this> */
    public function applicationScores(): HasMany
    {
        return $this->hasMany(ApplicationScore::class);
    }

    /** @return HasMany<RankingSnapshot, $this> */
    public function rankingSnapshots(): HasMany
    {
        return $this->hasMany(RankingSnapshot::class);
    }

    /** @return HasMany<ProvisionalList, $this> */
    public function provisionalLists(): HasMany
    {
        return $this->hasMany(ProvisionalList::class);
    }

    /** @return HasMany<DefinitiveList, $this> */
    public function definitiveLists(): HasMany
    {
        return $this->hasMany(DefinitiveList::class);
    }

    /** @return HasMany<ContestHousingUnit, $this> */
    public function contestHousingUnits(): HasMany
    {
        return $this->hasMany(ContestHousingUnit::class);
    }

    /** @return HasMany<TypologyAdequacyRule, $this> */
    public function typologyAdequacyRules(): HasMany
    {
        return $this->hasMany(TypologyAdequacyRule::class);
    }

    /** @return HasMany<HousingPreference, $this> */
    public function housingPreferences(): HasMany
    {
        return $this->hasMany(HousingPreference::class);
    }

    /** @return HasMany<AllocationRuleSet, $this> */
    public function allocationRuleSets(): HasMany
    {
        return $this->hasMany(AllocationRuleSet::class);
    }

    /** @return HasMany<AllocationRun, $this> */
    public function allocationRuns(): HasMany
    {
        return $this->hasMany(AllocationRun::class);
    }

    /** @return HasMany<Allocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /** @return HasMany<ReserveList, $this> */
    public function reserveLists(): HasMany
    {
        return $this->hasMany(ReserveList::class);
    }

    /** @return HasMany<AllocationReport, $this> */
    public function allocationReports(): HasMany
    {
        return $this->hasMany(AllocationReport::class);
    }

    /** @return HasMany<LotteryDraw, $this> */
    public function lotteryDraws(): HasMany
    {
        return $this->hasMany(LotteryDraw::class);
    }

    /** @return HasMany<RentRuleSet, $this> */
    public function rentRuleSets(): HasMany
    {
        return $this->hasMany(RentRuleSet::class);
    }

    /** @return HasMany<ContractTemplate, $this> */
    public function contractTemplates(): HasMany
    {
        return $this->hasMany(ContractTemplate::class);
    }

    /** @return HasMany<ContractClause, $this> */
    public function contractClauses(): HasMany
    {
        return $this->hasMany(ContractClause::class);
    }

    /** @return HasMany<Contract, $this> */
    public function leaseContracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasMany<SimulationRecommendedContest, $this>
     */
    public function simulationRecommendedContests(): HasMany
    {
        return $this->hasMany(SimulationRecommendedContest::class);
    }

    /**
     * @return HasMany<VisitAvailability, $this>
     */
    public function visitAvailabilities(): HasMany
    {
        return $this->hasMany(VisitAvailability::class);
    }

    /**
     * @return HasMany<VisitSlot, $this>
     */
    public function visitSlots(): HasMany
    {
        return $this->hasMany(VisitSlot::class);
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
     * @return HasMany<ListAutomationRun, $this>
     */
    public function listAutomationRuns(): HasMany
    {
        return $this->hasMany(ListAutomationRun::class);
    }

    /**
     * @return HasMany<ProcedureMinute, $this>
     */
    public function procedureMinutes(): HasMany
    {
        return $this->hasMany(ProcedureMinute::class);
    }

    /**
     * @return HasMany<ProcessConfirmation, $this>
     */
    public function processConfirmations(): HasMany
    {
        return $this->hasMany(ProcessConfirmation::class);
    }

    /**
     * @return HasMany<ContextualFaq, $this>
     */
    public function contextualFaqs(): HasMany
    {
        return $this->hasMany(ContextualFaq::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ContestStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('program', function (Builder $builder): void {
                $builder
                    ->where('status', ProgramStatus::Published->value)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where(fn (Builder $query) => $query
                        ->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', today()))
                    ->where(fn (Builder $query) => $query
                        ->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', today()));
            });
    }

    public function isOpenForApplications(): bool
    {
        return $this->status === ContestStatus::Published
            && $this->published_at?->isPast() === true
            && ($this->opens_at && $this->closes_at
                ? now()->between($this->opens_at, $this->closes_at)
                : false);
    }

    public function publicPhase(): string
    {
        if ($this->status === ContestStatus::Cancelled) {
            return 'cancelled';
        }

        if ($this->opens_at === null || $this->closes_at === null) {
            return 'upcoming';
        }

        if (now()->lt($this->opens_at)) {
            return 'upcoming';
        }

        if (now()->gt($this->closes_at)) {
            return 'closed';
        }

        return 'open';
    }
}
