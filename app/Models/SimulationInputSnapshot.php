<?php

namespace App\Models;

use Database\Factories\SimulationInputSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string>|null $preferred_typologies
 */
class SimulationInputSnapshot extends Model
{
    /** @use HasFactory<SimulationInputSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'simulation_session_id',
        'household_members_count',
        'adults_count',
        'dependents_count',
        'disabled_members_count',
        'monthly_income',
        'annual_income',
        'current_monthly_rent',
        'housing_status',
        'preferred_parishes',
        'preferred_typologies',
        'input_data',
        'completeness_score',
        'contains_personal_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'household_members_count' => 'integer',
            'adults_count' => 'integer',
            'dependents_count' => 'integer',
            'disabled_members_count' => 'integer',
            'monthly_income' => 'decimal:2',
            'annual_income' => 'decimal:2',
            'current_monthly_rent' => 'decimal:2',
            'preferred_parishes' => 'array',
            'preferred_typologies' => 'array',
            'input_data' => 'array',
            'completeness_score' => 'decimal:2',
            'contains_personal_data' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SimulationSession, $this>
     */
    public function simulationSession(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class);
    }
}
