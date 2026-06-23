<?php

namespace App\Models;

use App\Enums\VisitCancellationReason;
use App\Enums\VisitStatus;
use Database\Factories\HousingVisitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class HousingVisit extends Model
{
    /** @use HasFactory<HousingVisitFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VisitStatus::class,
            'scheduled_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancellation_reason' => VisitCancellationReason::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'visit_number';
    }

    /**
     * @return BelongsTo<VisitSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(VisitSlot::class, 'visit_slot_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<HousingUnit, $this>
     */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return BelongsTo<HousingVisit, $this>
     */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(HousingVisit::class, 'rescheduled_from_id');
    }

    /**
     * @return HasMany<HousingVisitStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(HousingVisitStatusHistory::class)->latest('changed_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCandidate(Builder $query, User|int $user): Builder
    {
        return $query->where('candidate_user_id', $user instanceof User ? $user->id : $user);
    }

    public function belongsToCandidate(User $user): bool
    {
        return $this->candidate_user_id === $user->id;
    }

    public function isActive(): bool
    {
        $status = VisitStatus::tryFrom((string) $this->getRawOriginal('status'));

        return $status?->isActive() ?? false;
    }
}
