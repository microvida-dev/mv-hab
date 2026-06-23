<?php

namespace App\Models;

use App\Enums\ImpedimentSeverity;
use App\Enums\ImpedimentType;
use Database\Factories\SimulationImpedimentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SimulationImpediment extends Model
{
    /** @use HasFactory<SimulationImpedimentFactory> */
    use HasFactory;

    protected $fillable = [
        'simulation_session_id',
        'simulation_result_id',
        'type',
        'severity',
        'code',
        'title',
        'message',
        'recommendation',
        'is_blocking',
        'related_field',
        'related_model_type',
        'related_model_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ImpedimentType::class,
            'severity' => ImpedimentSeverity::class,
            'is_blocking' => 'boolean',
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
     * @return MorphTo<Model, $this>
     */
    public function relatedModel(): MorphTo
    {
        return $this->morphTo();
    }
}
