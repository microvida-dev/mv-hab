<?php

namespace App\Models;

use App\Enums\AnonymizationMode;
use App\Enums\DefinitiveListStatus;
use Database\Factories\DefinitiveListFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefinitiveList extends Model
{
    /** @use HasFactory<DefinitiveListFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'list_number', 'status', 'generated_by', 'generated_at', 'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at', 'published_by', 'published_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => DefinitiveListStatus::class,
            'publication_starts_at' => 'datetime',
            'publication_ends_at' => 'datetime',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'public_visibility' => 'boolean',
            'anonymization_mode' => AnonymizationMode::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'list_number';
    }

    /** @return BelongsTo<ProvisionalList, $this> */
    public function provisionalList(): BelongsTo
    {
        return $this->belongsTo(ProvisionalList::class);
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

    /** @return BelongsTo<RankingSnapshot, $this> */
    public function rankingSnapshot(): BelongsTo
    {
        return $this->belongsTo(RankingSnapshot::class);
    }

    /** @return BelongsTo<ScoringRun, $this> */
    public function scoringRun(): BelongsTo
    {
        return $this->belongsTo(ScoringRun::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return HasMany<DefinitiveListEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(DefinitiveListEntry::class)->orderBy('rank_position')->orderBy('id');
    }

    /** @return MorphMany<ListPublication, $this> */
    public function publications(): MorphMany
    {
        return $this->morphMany(ListPublication::class, 'publishable')->latest();
    }

    /** @return HasMany<ListChangeLog, $this> */
    public function changeLogs(): HasMany
    {
        return $this->hasMany(ListChangeLog::class);
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

    /**
     * @param  Builder<DefinitiveList>  $query
     * @return Builder<DefinitiveList>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [DefinitiveListStatus::Approved->value, DefinitiveListStatus::Published->value, DefinitiveListStatus::Locked->value]);
    }

    /**
     * @param  Builder<DefinitiveList>  $query
     * @return Builder<DefinitiveList>
     */
    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('status', DefinitiveListStatus::Locked->value);
    }
}
