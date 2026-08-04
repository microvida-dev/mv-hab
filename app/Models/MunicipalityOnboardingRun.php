<?php

namespace App\Models;

use App\Enums\MunicipalityOnboardingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $operation_id
 * @property string $municipality_code
 * @property int|null $municipality_id
 * @property int $actor_id
 * @property int|null $admin_user_id
 * @property MunicipalityOnboardingStatus $status
 * @property string $input_fingerprint
 * @property string $role_template_key
 * @property string $role_template_version
 * @property string $role_template_fingerprint
 * @property int $attempt_count
 * @property string|null $failure_code
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 */
class MunicipalityOnboardingRun extends Model
{
    protected $fillable = [
        'operation_id',
        'municipality_code',
        'municipality_id',
        'actor_id',
        'admin_user_id',
        'status',
        'input_fingerprint',
        'role_template_key',
        'role_template_version',
        'role_template_fingerprint',
        'attempt_count',
        'failure_code',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MunicipalityOnboardingStatus::class,
            'attempt_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn (): bool => false);
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /** @return HasOne<MunicipalAdministratorInvitation, $this> */
    public function invitation(): HasOne
    {
        return $this->hasOne(MunicipalAdministratorInvitation::class, 'onboarding_run_id');
    }
}
