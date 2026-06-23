<?php

namespace App\Models;

use App\Enums\ApplicationPrefillStatus;
use Database\Factories\ApplicationPrefillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationPrefill extends Model
{
    /** @use HasFactory<ApplicationPrefillFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'application_id',
        'simulation_session_id',
        'candidate_data_reuse_profile_id',
        'status',
        'prefill_payload',
        'fields_included',
        'fields_excluded',
        'warnings',
        'confirmed_by_user_at',
        'applied_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationPrefillStatus::class,
            'prefill_payload' => 'array',
            'fields_included' => 'array',
            'fields_excluded' => 'array',
            'warnings' => 'array',
            'confirmed_by_user_at' => 'datetime',
            'applied_at' => 'datetime',
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
     * @return BelongsTo<CandidateDataReuseProfile, $this>
     */
    public function candidateDataReuseProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateDataReuseProfile::class);
    }
}
