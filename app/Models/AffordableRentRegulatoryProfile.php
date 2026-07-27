<?php

namespace App\Models;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryProfileStatus;
use Carbon\CarbonInterface;
use Database\Factories\AffordableRentRegulatoryProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $municipality_id
 * @property int|null $parent_profile_id
 * @property AffordableRentLegalRegime $legal_regime
 * @property RegulatoryProfileStatus $status
 * @property RegulatoryConfigurationStatus $configuration_status
 * @property Carbon $effective_from
 * @property Carbon|null $effective_until
 * @property string|null $maximum_effort_rate_percentage
 * @property string|null $minimum_adult_monthly_income
 * @property string|null $annual_income_base_limit
 * @property string|null $second_person_increment
 * @property string|null $additional_person_increment
 * @property bool $rent_limits_configured
 * @property bool $eligibility_rules_configured
 * @property bool $typology_rules_configured
 * @property bool $contract_terms_configured
 * @property array<string, mixed>|null $metadata
 */
class AffordableRentRegulatoryProfile extends Model
{
    /** @use HasFactory<AffordableRentRegulatoryProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'legal_regime' => AffordableRentLegalRegime::class,
            'status' => RegulatoryProfileStatus::class,
            'configuration_status' => RegulatoryConfigurationStatus::class,
            'effective_from' => 'date',
            'effective_until' => 'date',
            'maximum_effort_rate_percentage' => 'decimal:2',
            'minimum_adult_monthly_income' => 'decimal:2',
            'annual_income_base_limit' => 'decimal:2',
            'second_person_increment' => 'decimal:2',
            'additional_person_increment' => 'decimal:2',
            'rent_limits_configured' => 'boolean',
            'eligibility_rules_configured' => 'boolean',
            'typology_rules_configured' => 'boolean',
            'contract_terms_configured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<self, $this> */
    public function parentProfile(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_profile_id');
    }

    /** @return HasMany<self, $this> */
    public function municipalOverlays(): HasMany
    {
        return $this->hasMany(self::class, 'parent_profile_id');
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

    /** @return HasMany<RegulatorySnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(RegulatorySnapshot::class, 'regulatory_profile_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveAt(Builder $query, CarbonInterface $referenceDate): Builder
    {
        return $query
            ->where('status', RegulatoryProfileStatus::Active->value)
            ->whereDate('effective_from', '<=', $referenceDate->toDateString())
            ->where(fn (Builder $builder) => $builder
                ->whereNull('effective_until')
                ->orWhereDate('effective_until', '>=', $referenceDate->toDateString()));
    }
}
