<?php

namespace App\Models;

use App\Enums\SimulationResultStatus;
use App\Enums\SimulationScope;
use App\Enums\SimulationSessionStatus;
use Database\Factories\SimulationSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property SimulationScope $scope
 * @property SimulationSessionStatus $status
 * @property SimulationResultStatus $result_status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $saved_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $converted_at
 */
class SimulationSession extends Model
{
    /** @use HasFactory<SimulationSessionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'adhesion_registration_id',
        'application_id',
        'scope',
        'status',
        'result_status',
        'started_at',
        'completed_at',
        'saved_at',
        'expires_at',
        'converted_at',
        'source',
        'ip_hash',
        'user_agent_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => SimulationScope::class,
            'status' => SimulationSessionStatus::class,
            'result_status' => SimulationResultStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'saved_at' => 'datetime',
            'expires_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AdhesionRegistration, $this>
     */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasOne<SimulationInputSnapshot, $this>
     */
    public function inputSnapshot(): HasOne
    {
        return $this->hasOne(SimulationInputSnapshot::class);
    }

    /**
     * @return HasOne<SimulationResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(SimulationResult::class);
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

    /**
     * @return HasMany<ApplicationPrefill, $this>
     */
    public function applicationPrefills(): HasMany
    {
        return $this->hasMany(ApplicationPrefill::class);
    }

    /**
     * @return HasMany<ApplicationSimulationInconsistency, $this>
     */
    public function applicationInconsistencies(): HasMany
    {
        return $this->hasMany(ApplicationSimulationInconsistency::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function belongsToUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isAnonymous(): bool
    {
        return $this->user_id === null
            && SimulationScope::tryFrom((string) $this->getAttribute('scope')) === SimulationScope::Anonymous;
    }
}
