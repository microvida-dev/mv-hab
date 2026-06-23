<?php

namespace App\Models;

use App\Enums\SimulationContestMatchStatus;
use Database\Factories\SimulationRecommendedContestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationRecommendedContest extends Model
{
    /** @use HasFactory<SimulationRecommendedContestFactory> */
    use HasFactory;

    protected $fillable = [
        'simulation_session_id',
        'simulation_result_id',
        'program_id',
        'contest_id',
        'match_status',
        'match_score',
        'public_status',
        'opens_at',
        'closes_at',
        'recommended_typologies',
        'rent_min',
        'rent_max',
        'reasons',
        'warnings',
        'cta_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_status' => SimulationContestMatchStatus::class,
            'match_score' => 'decimal:2',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'recommended_typologies' => 'array',
            'rent_min' => 'decimal:2',
            'rent_max' => 'decimal:2',
            'reasons' => 'array',
            'warnings' => 'array',
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
     * @return BelongsTo<SimulationResult, $this>
     */
    public function simulationResult(): BelongsTo
    {
        return $this->belongsTo(SimulationResult::class);
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
}
