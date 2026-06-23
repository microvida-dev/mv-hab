<?php

namespace App\Models;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRunStatus;
use App\Enums\AllocationStatus;
use Database\Factories\AllocationRunFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllocationRun extends Model
{
    /** @use HasFactory<AllocationRunFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'run_number', 'status', 'started_by', 'started_at', 'completed_at', 'failed_at', 'failure_reason', 'locked_at', 'locked_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => AllocationRunStatus::class,
            'allocation_method' => AllocationMethod::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
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

    /** @return BelongsTo<User, $this> */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /** @return BelongsTo<User, $this> */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /** @return HasMany<Allocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /** @return HasOne<LotteryRun, $this> */
    public function lotteryRun(): HasOne
    {
        return $this->hasOne(LotteryRun::class);
    }

    /** @return HasOne<ReserveList, $this> */
    public function reserveList(): HasOne
    {
        return $this->hasOne(ReserveList::class);
    }

    /** @return HasMany<AllocationReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(AllocationReport::class);
    }

    /**
     * @param  Builder<AllocationRun>  $query
     * @return Builder<AllocationRun>
     */
    public function scopeReadyForContract(Builder $query): Builder
    {
        return $query->whereHas('allocations', fn (Builder $builder) => $builder
            ->whereIn('status', [
                AllocationStatus::Accepted->value,
                AllocationStatus::ReadyForContract->value,
            ])
            ->whereNotNull('ready_for_contract_at')
            ->whereNull('superseded_by_allocation_id'));
    }
}
