<?php

namespace App\Models;

use App\Enums\RentCalculationMethod;
use App\Enums\RentCalculationStatus;
use Database\Factories\RentCalculationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentCalculation extends Model
{
    /** @use HasFactory<RentCalculationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'manual_rent', 'approved_at', 'approved_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RentCalculationStatus::class,
            'calculation_method' => RentCalculationMethod::class,
            'monthly_household_income' => 'decimal:2',
            'annual_household_income' => 'decimal:2',
            'monthly_income_per_capita' => 'decimal:2',
            'annual_income_per_capita' => 'decimal:2',
            'calculated_effort_rate_percentage' => 'decimal:4',
            'configured_effort_rate_percentage' => 'decimal:2',
            'base_rent' => 'decimal:2',
            'minimum_rent' => 'decimal:2',
            'maximum_rent' => 'decimal:2',
            'applicable_rent' => 'decimal:2',
            'manual_rent' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<RentRuleSet, $this> */
    public function rentRuleSet(): BelongsTo
    {
        return $this->belongsTo(RentRuleSet::class);
    }

    /** @return BelongsTo<Allocation, $this> */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
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

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<ContestHousingUnit, $this> */
    public function contestHousingUnit(): BelongsTo
    {
        return $this->belongsTo(ContestHousingUnit::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /** @return BelongsTo<User, $this> */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<RentCalculationDetail, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(RentCalculationDetail::class);
    }

    /** @return HasMany<RentManualReview, $this> */
    public function manualReviews(): HasMany
    {
        return $this->hasMany(RentManualReview::class);
    }
}
