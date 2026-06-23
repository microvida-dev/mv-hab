<?php

namespace App\Models;

use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'citizen_id',
        'name',
        'household_type',
        'monthly_income',
        'members_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_income' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Citizen, $this> */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /** @return BelongsTo<AdhesionRegistration, $this> */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /** @return HasMany<HouseholdMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    /** @return HasMany<IncomeRecord, $this> */
    public function incomeRecords(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
    }

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    /** @return HasMany<HousingApplication, $this> */
    public function housingApplications(): HasMany
    {
        return $this->hasMany(HousingApplication::class);
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** @return HasMany<RentCalculation, $this> */
    public function rentCalculations(): HasMany
    {
        return $this->hasMany(RentCalculation::class);
    }

    /** @return HasMany<Contract, $this> */
    public function leaseContracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function isCandidateHousehold(): bool
    {
        return $this->adhesion_registration_id !== null;
    }

    public function totalMonthlyIncome(): float
    {
        return (float) $this->incomeRecords()->sum('monthly_amount');
    }

    public function totalAnnualIncome(): float
    {
        return (float) $this->incomeRecords()->sum('annual_amount');
    }
}
