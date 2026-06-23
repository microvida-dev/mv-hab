<?php

namespace App\Models;

use Database\Factories\SimulatorConfigurationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulatorConfiguration extends Model
{
    /** @use HasFactory<SimulatorConfigurationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'municipality_id',
        'program_id',
        'contest_id',
        'name',
        'is_active',
        'anonymous_simulator_enabled',
        'candidate_simulator_enabled',
        'max_recommended_contests',
        'default_effort_rate',
        'session_retention_days',
        'settings',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'anonymous_simulator_enabled' => 'boolean',
            'candidate_simulator_enabled' => 'boolean',
            'max_recommended_contests' => 'integer',
            'default_effort_rate' => 'decimal:2',
            'session_retention_days' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
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
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
