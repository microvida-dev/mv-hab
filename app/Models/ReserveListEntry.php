<?php

namespace App\Models;

use App\Enums\ReserveListEntryStatus;
use Database\Factories\ReserveListEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ReserveListEntryStatus $status
 */
class ReserveListEntry extends Model
{
    /** @use HasFactory<ReserveListEntryFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'called_at', 'offered_at', 'accepted_at', 'refused_at', 'expired_at', 'withdrawn_at', 'removed_at', 'linked_allocation_id', 'replacement_for_allocation_id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ReserveListEntryStatus::class,
            'called_at' => 'datetime',
            'offered_at' => 'datetime',
            'accepted_at' => 'datetime',
            'refused_at' => 'datetime',
            'expired_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReserveList, $this>
     */
    public function reserveList(): BelongsTo
    {
        return $this->belongsTo(ReserveList::class);
    }

    /**
     * @return BelongsTo<AllocationRun, $this>
     */
    public function allocationRun(): BelongsTo
    {
        return $this->belongsTo(AllocationRun::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<DefinitiveListEntry, $this>
     */
    public function definitiveListEntry(): BelongsTo
    {
        return $this->belongsTo(DefinitiveListEntry::class);
    }

    /**
     * @return BelongsTo<Allocation, $this>
     */
    public function linkedAllocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class, 'linked_allocation_id');
    }

    /**
     * @return BelongsTo<Allocation, $this>
     */
    public function replacementForAllocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class, 'replacement_for_allocation_id');
    }

    /**
     * @param  Builder<ReserveListEntry>  $query
     * @return Builder<ReserveListEntry>
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', ReserveListEntryStatus::Waiting->value);
    }
}
