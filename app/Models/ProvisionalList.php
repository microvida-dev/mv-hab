<?php

namespace App\Models;

use App\Enums\AnonymizationMode;
use App\Enums\ProvisionalListStatus;
use Carbon\CarbonInterface;
use Database\Factories\ProvisionalListFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProvisionalList extends Model
{
    /** @use HasFactory<ProvisionalListFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'list_number', 'status', 'generated_by', 'generated_at', 'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at', 'published_by', 'published_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ProvisionalListStatus::class,
            'publication_starts_at' => 'datetime',
            'publication_ends_at' => 'datetime',
            'complaint_period_starts_at' => 'datetime',
            'complaint_period_ends_at' => 'datetime',
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

    /** @return HasMany<ProvisionalListEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(ProvisionalListEntry::class)->orderBy('rank_position')->orderBy('id');
    }

    /** @return HasMany<Complaint, $this> */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /** @return HasMany<Hearing, $this> */
    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    /** @return MorphMany<ListPublication, $this> */
    public function publications(): MorphMany
    {
        return $this->morphMany(ListPublication::class, 'publishable')->latest();
    }

    /** @return HasOne<DefinitiveList, $this> */
    public function definitiveList(): HasOne
    {
        return $this->hasOne(DefinitiveList::class);
    }

    /**
     * @param  Builder<ProvisionalList>  $query
     * @return Builder<ProvisionalList>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ProvisionalListStatus::Published->value,
            ProvisionalListStatus::ComplaintPeriodOpen->value,
            ProvisionalListStatus::ComplaintPeriodClosed->value,
        ]);
    }

    public function isComplaintPeriodOpen(): bool
    {
        $status = $this->getAttribute('status');
        $startsAt = $this->getAttribute('complaint_period_starts_at');
        $endsAt = $this->getAttribute('complaint_period_ends_at');

        return $status instanceof ProvisionalListStatus
            && $status === ProvisionalListStatus::ComplaintPeriodOpen
            && ($startsAt === null || ($startsAt instanceof CarbonInterface && $startsAt->lte(now())))
            && ($endsAt === null || ($endsAt instanceof CarbonInterface && $endsAt->gte(now())));
    }
}
