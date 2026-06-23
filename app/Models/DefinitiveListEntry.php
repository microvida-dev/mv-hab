<?php

namespace App\Models;

use App\Enums\DefinitiveListStatus;
use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use Database\Factories\DefinitiveListEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ListEntryStatus $status
 * @property ListEntryType $entry_type
 */
class DefinitiveListEntry extends Model
{
    /** @use HasFactory<DefinitiveListEntryFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'entry_type', 'rank_position', 'total_score', 'previous_rank_position', 'previous_total_score', 'public_identifier', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'entry_type' => ListEntryType::class,
            'status' => ListEntryStatus::class,
            'total_score' => 'decimal:2',
            'previous_total_score' => 'decimal:2',
            'changed_after_complaint' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<DefinitiveList, $this> */
    public function definitiveList(): BelongsTo
    {
        return $this->belongsTo(DefinitiveList::class);
    }

    /** @return BelongsTo<ProvisionalListEntry, $this> */
    public function provisionalListEntry(): BelongsTo
    {
        return $this->belongsTo(ProvisionalListEntry::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<ApplicationScore, $this> */
    public function applicationScore(): BelongsTo
    {
        return $this->belongsTo(ApplicationScore::class);
    }

    /** @return BelongsTo<RankingEntry, $this> */
    public function rankingEntry(): BelongsTo
    {
        return $this->belongsTo(RankingEntry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Allocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /** @return HasMany<ReserveListEntry, $this> */
    public function reserveListEntries(): HasMany
    {
        return $this->hasMany(ReserveListEntry::class);
    }

    /**
     * @param  Builder<DefinitiveListEntry>  $query
     * @return Builder<DefinitiveListEntry>
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query->where('entry_type', ListEntryType::Ranked->value)
            ->whereIn('status', [ListEntryStatus::Ranked->value, ListEntryStatus::ChangedAfterComplaint->value]);
    }

    /**
     * @param  Builder<DefinitiveListEntry>  $query
     * @return Builder<DefinitiveListEntry>
     */
    public function scopeEligibleForAllocation(Builder $query): Builder
    {
        return $query->where('entry_type', ListEntryType::Ranked->value)
            ->whereIn('status', [ListEntryStatus::Ranked->value, ListEntryStatus::ChangedAfterComplaint->value])
            ->whereHas('definitiveList', fn (Builder $builder) => $builder->whereIn('status', [
                DefinitiveListStatus::Approved->value,
                DefinitiveListStatus::Published->value,
                DefinitiveListStatus::Locked->value,
            ]))
            ->whereDoesntHave('application.complaints', fn (Builder $builder) => $builder->whereNotIn('status', [
                'accepted',
                'partially_accepted',
                'rejected',
                'withdrawn',
                'cancelled',
                'closed',
            ]))
            ->whereDoesntHave('application.hearings', fn (Builder $builder) => $builder->whereIn('status', [
                'draft',
                'issued',
                'open',
                'submitted',
                'under_review',
            ]));
    }
}
