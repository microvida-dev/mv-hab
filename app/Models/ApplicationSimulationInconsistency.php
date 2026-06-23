<?php

namespace App\Models;

use App\Enums\InconsistencySeverity;
use App\Enums\InconsistencyType;
use Database\Factories\ApplicationSimulationInconsistencyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationSimulationInconsistency extends Model
{
    /** @use HasFactory<ApplicationSimulationInconsistencyFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'is_resolved', 'resolved_by', 'resolved_at', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InconsistencyType::class,
            'severity' => InconsistencySeverity::class,
            'simulation_value' => 'array',
            'application_value' => 'array',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<SimulationSession, $this>
     */
    public function simulationSession(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }
}
