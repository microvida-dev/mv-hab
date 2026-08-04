<?php

namespace App\Models;

use App\Enums\MunicipalAdministratorInvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $onboarding_run_id
 * @property int $user_id
 * @property string $idempotency_key
 * @property MunicipalAdministratorInvitationStatus $status
 * @property int $attempt_count
 * @property Carbon|null $queued_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $expires_at
 * @property string|null $last_failure_code
 */
class MunicipalAdministratorInvitation extends Model
{
    protected $fillable = [
        'onboarding_run_id',
        'user_id',
        'idempotency_key',
        'status',
        'attempt_count',
        'queued_at',
        'sent_at',
        'failed_at',
        'consumed_at',
        'expires_at',
        'last_failure_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => MunicipalAdministratorInvitationStatus::class,
            'attempt_count' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'consumed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn (): bool => false);
    }

    /** @return BelongsTo<MunicipalityOnboardingRun, $this> */
    public function onboardingRun(): BelongsTo
    {
        return $this->belongsTo(MunicipalityOnboardingRun::class, 'onboarding_run_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
