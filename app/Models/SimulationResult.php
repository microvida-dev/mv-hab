<?php

namespace App\Models;

use App\Enums\RentEstimateStatus;
use App\Enums\SimulationResultStatus;
use App\Enums\TypologyRecommendationStatus;
use Database\Factories\SimulationResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationResult extends Model
{
    /** @use HasFactory<SimulationResultFactory> */
    use HasFactory;

    protected $fillable = [
        'simulation_session_id',
        'result_status',
        'eligibility_summary',
        'eligibility_score',
        'eligibility_payload',
        'typology_status',
        'recommended_typology',
        'recommended_bedrooms',
        'typology_payload',
        'rent_status',
        'estimated_rent_min',
        'estimated_rent_max',
        'estimated_effort_rate',
        'rent_payload',
        'recommendations_payload',
        'impediments_count',
        'blocking_impediments_count',
        'recommended_contests_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_status' => SimulationResultStatus::class,
            'eligibility_score' => 'decimal:2',
            'eligibility_payload' => 'array',
            'typology_status' => TypologyRecommendationStatus::class,
            'recommended_bedrooms' => 'integer',
            'typology_payload' => 'array',
            'rent_status' => RentEstimateStatus::class,
            'estimated_rent_min' => 'decimal:2',
            'estimated_rent_max' => 'decimal:2',
            'estimated_effort_rate' => 'decimal:2',
            'rent_payload' => 'array',
            'recommendations_payload' => 'array',
            'impediments_count' => 'integer',
            'blocking_impediments_count' => 'integer',
            'recommended_contests_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SimulationSession, $this>
     */
    public function simulationSession(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class);
    }

    /**
     * @return HasMany<SimulationImpediment, $this>
     */
    public function impediments(): HasMany
    {
        return $this->hasMany(SimulationImpediment::class);
    }

    /**
     * @return HasMany<SimulationRecommendedContest, $this>
     */
    public function recommendedContests(): HasMany
    {
        return $this->hasMany(SimulationRecommendedContest::class)
            ->orderByDesc('match_score');
    }
}
