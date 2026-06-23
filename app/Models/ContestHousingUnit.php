<?php

namespace App\Models;

use App\Enums\ContestHousingUnitStatus;
use Database\Factories\ContestHousingUnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContestHousingUnitStatus $status
 */
class ContestHousingUnit extends Model
{
    /** @use HasFactory<ContestHousingUnitFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContestHousingUnitStatus::class,
            'availability_starts_at' => 'datetime',
            'availability_ends_at' => 'datetime',
            'accessible' => 'boolean',
            'monthly_rent' => 'decimal:2',
            'estimated_expenses' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
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
     * @return HasMany<HousingPreference, $this>
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(HousingPreference::class);
    }

    /**
     * @return HasMany<Allocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /**
     * @return HasMany<AllocationOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(AllocationOffer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', ContestHousingUnitStatus::Available->value);
    }
}
