<?php

namespace App\Models;

use App\Enums\CandidateDataReuseProfileStatus;
use Database\Factories\CandidateDataReuseProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property CandidateDataReuseProfileStatus $status
 * @property Carbon|null $last_confirmed_at
 * @property Carbon|null $expires_at
 */
class CandidateDataReuseProfile extends Model
{
    /** @use HasFactory<CandidateDataReuseProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'adhesion_registration_id',
        'profile_number',
        'status',
        'registration_snapshot',
        'household_snapshot',
        'income_snapshot',
        'housing_snapshot',
        'documents_snapshot',
        'source_payload',
        'last_confirmed_at',
        'expires_at',
        'created_from_simulation_session_id',
        'created_from_application_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CandidateDataReuseProfileStatus::class,
            'registration_snapshot' => 'array',
            'household_snapshot' => 'array',
            'income_snapshot' => 'array',
            'housing_snapshot' => 'array',
            'documents_snapshot' => 'array',
            'source_payload' => 'array',
            'last_confirmed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
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
     * @return BelongsTo<SimulationSession, $this>
     */
    public function createdFromSimulationSession(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class, 'created_from_simulation_session_id');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function createdFromApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'created_from_application_id');
    }

    /**
     * @return HasMany<ApplicationPrefill, $this>
     */
    public function applicationPrefills(): HasMany
    {
        return $this->hasMany(ApplicationPrefill::class);
    }
}
