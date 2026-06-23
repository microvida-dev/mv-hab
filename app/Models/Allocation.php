<?php

namespace App\Models;

use App\Enums\AllocationMethod;
use App\Enums\AllocationStatus;
use Database\Factories\AllocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property AllocationMethod $allocation_method
 * @property AllocationStatus $status
 */
class Allocation extends Model
{
    /** @use HasFactory<AllocationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'allocated_by', 'allocated_at', 'offered_at', 'acceptance_deadline_at', 'accepted_at', 'refused_at', 'expired_at', 'withdrawn_at', 'cancelled_at', 'ready_for_contract_at', 'superseded_by_allocation_id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'allocation_method' => AllocationMethod::class,
            'status' => AllocationStatus::class,
            'allocated_at' => 'datetime',
            'offered_at' => 'datetime',
            'acceptance_deadline_at' => 'datetime',
            'accepted_at' => 'datetime',
            'refused_at' => 'datetime',
            'expired_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'ready_for_contract_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AllocationRun, $this> */
    public function allocationRun(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class);
    }

    /** @return BelongsTo<AllocationRuleSet, $this> */
    public function allocationRuleSet(): BelongsTo
    {
        return $this->belongsTo(AllocationRuleSet::class);
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

    /** @return BelongsTo<DefinitiveList, $this> */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
    }

    /** @return BelongsTo<DefinitiveListEntry, $this> */
    public function definitiveListEntry(): BelongsTo
    {
        return $this->belongsTo(DefinitiveListEntry::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<ContestHousingUnit, $this> */
    public function contestHousingUnit(): BelongsTo
    {
        return $this->belongsTo(ContestHousingUnit::class);
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    /** @return BelongsTo<Allocation, $this> */
    public function supersededByAllocation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_allocation_id');
    }

    /** @return HasMany<AllocationOffer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(AllocationOffer::class);
    }

    /** @return HasMany<RentCalculation, $this> */
    public function rentCalculations(): HasMany
    {
        return $this->hasMany(RentCalculation::class);
    }

    /** @return HasOne<Contract, $this> */
    public function leaseContract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    /** @return HasOne<Contract, $this> */
    public function activeLeaseContract(): HasOne
    {
        return $this->hasOne(Contract::class)
            ->whereIn('status', ['preparation', 'issued', 'signed', 'active'])
            ->latestOfMany();
    }

    /** @return HasOne<ContractDeposit, $this> */
    public function contractDeposit(): HasOne
    {
        return $this->hasOne(ContractDeposit::class);
    }

    /** @return HasOne<AllocationOffer, $this> */
    public function activeOffer(): HasOne
    {
        return $this->hasOne(AllocationOffer::class)->latestOfMany();
    }

    /**
     * @param  Builder<Allocation>  $query
     * @return Builder<Allocation>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AllocationStatus::Proposed->value,
            AllocationStatus::Offered->value,
            AllocationStatus::Accepted->value,
            AllocationStatus::ReadyForContract->value,
        ]);
    }

    /**
     * @param  Builder<Allocation>  $query
     * @return Builder<Allocation>
     */
    public function scopeReadyForContract(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AllocationStatus::Accepted->value,
            AllocationStatus::ReadyForContract->value,
        ])
            ->whereNotNull('ready_for_contract_at')
            ->whereNull('superseded_by_allocation_id');
    }
}
