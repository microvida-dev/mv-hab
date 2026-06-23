<?php

namespace App\Models;

use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use Database\Factories\CurrentHousingSituationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property HousingStatus|null $housing_status
 */
class CurrentHousingSituation extends Model
{
    /** @use HasFactory<CurrentHousingSituationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'housing_status',
        'current_address',
        'current_postal_code',
        'current_city',
        'current_parish',
        'current_municipality',
        'resides_in_municipality',
        'residence_years_in_municipality',
        'works_in_municipality',
        'workplace_municipality',
        'current_housing_typology',
        'current_housing_rooms',
        'current_housing_condition',
        'current_monthly_rent',
        'current_housing_expense',
        'is_overcrowded',
        'is_at_risk_of_eviction',
        'is_homeless',
        'is_temporary_accommodation',
        'is_domestic_violence_victim',
        'has_accessibility_needs',
        'has_high_rent_burden',
        'request_reason',
        'additional_notes',
    ];

    protected function casts(): array
    {
        return [
            'housing_status' => HousingStatus::class,
            'resides_in_municipality' => 'boolean',
            'residence_years_in_municipality' => 'decimal:2',
            'works_in_municipality' => 'boolean',
            'current_housing_condition' => HousingCondition::class,
            'current_monthly_rent' => 'decimal:2',
            'current_housing_expense' => 'decimal:2',
            'is_overcrowded' => 'boolean',
            'is_at_risk_of_eviction' => 'boolean',
            'is_homeless' => 'boolean',
            'is_temporary_accommodation' => 'boolean',
            'is_domestic_violence_victim' => 'boolean',
            'has_accessibility_needs' => 'boolean',
            'has_high_rent_burden' => 'boolean',
        ];
    }

    /** @return BelongsTo<AdhesionRegistration, $this> */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function effortRate(float $monthlyIncome): ?float
    {
        if ($monthlyIncome <= 0 || $this->current_monthly_rent === null) {
            return null;
        }

        return round(((float) $this->current_monthly_rent / $monthlyIncome) * 100, 1);
    }
}
