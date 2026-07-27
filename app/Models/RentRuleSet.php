<?php

namespace App\Models;

use App\Enums\RentCalculationMethod;
use App\Enums\RentRuleSetStatus;
use Carbon\CarbonInterface;
use Database\Factories\RentRuleSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $regulatory_profile_id
 * @property RentCalculationMethod $calculation_method
 * @property RentRuleSetStatus $status
 * @property string|null $effort_rate_percentage
 * @property string|null $minimum_rent
 * @property string|null $maximum_rent
 */
class RentRuleSet extends Model
{
    /** @use HasFactory<RentRuleSetFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RentRuleSetStatus::class,
            'calculation_method' => RentCalculationMethod::class,
            'effort_rate_percentage' => 'decimal:2',
            'minimum_rent' => 'decimal:2',
            'maximum_rent' => 'decimal:2',
            'minimum_effort_rate_percentage' => 'decimal:2',
            'maximum_effort_rate_percentage' => 'decimal:2',
            'deposit_months' => 'decimal:2',
            'minimum_deposit' => 'decimal:2',
            'maximum_deposit' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'requires_manual_approval' => 'boolean',
            'allow_manual_override' => 'boolean',
        ];
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

    /** @return BelongsTo<AffordableRentRegulatoryProfile, $this> */
    public function regulatoryProfile(): BelongsTo
    {
        return $this->belongsTo(AffordableRentRegulatoryProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<RentRule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(RentRule::class)->orderBy('priority_order');
    }

    /** @return HasMany<RentCalculation, $this> */
    public function calculations(): HasMany
    {
        return $this->hasMany(RentCalculation::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->activeAt(today());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveAt(Builder $query, CarbonInterface $referenceDate): Builder
    {
        return $query->where('status', RentRuleSetStatus::Active->value)
            ->where(fn (Builder $builder) => $builder->whereNull('effective_from')->orWhereDate('effective_from', '<=', $referenceDate->toDateString()))
            ->where(fn (Builder $builder) => $builder->whereNull('effective_until')->orWhereDate('effective_until', '>=', $referenceDate->toDateString()));
    }
}
