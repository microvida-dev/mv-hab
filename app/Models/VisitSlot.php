<?php

namespace App\Models;

use App\Enums\VisitSlotStatus;
use Database\Factories\VisitSlotFactory;
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
class VisitSlot extends Model
{
    /** @use HasFactory<VisitSlotFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'booked_count', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => VisitSlotStatus::class,
        ];
    }

    /**
     * @return BelongsTo<VisitAvailability, $this>
     */
    public function availability(): BelongsTo
    {
        return $this->belongsTo(VisitAvailability::class, 'visit_availability_id');
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
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    /**
     * @return HasMany<HousingVisit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(HousingVisit::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [VisitSlotStatus::Available->value, VisitSlotStatus::Reserved->value])
            ->whereColumn('booked_count', '<', 'capacity')
            ->where('starts_at', '>', now());
    }

    public function isBookable(): bool
    {
        return in_array(VisitSlotStatus::tryFrom((string) $this->getRawOriginal('status')), [VisitSlotStatus::Available, VisitSlotStatus::Reserved], true)
            && $this->booked_count < $this->capacity
            && $this->starts_at !== null
            && $this->starts_at->isFuture();
    }

    public function remainingCapacity(): int
    {
        return max(0, (int) $this->capacity - (int) $this->booked_count);
    }
}
