<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ProgramStatus $status
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'municipality_id',
        'created_by',
        'updated_by',
        'name',
        'slug',
        'summary',
        'description',
        'legal_basis',
        'status',
        'starts_at',
        'ends_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProgramStatus::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
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

    /** @return HasMany<ProgramRule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(ProgramRule::class)->orderBy('sort_order');
    }

    /** @return HasMany<Contest, $this> */
    public function contests(): HasMany
    {
        return $this->hasMany(Contest::class);
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
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ProgramStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $builder) => $builder
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', today()))
            ->where(fn (Builder $builder) => $builder
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', today()));
    }
}
