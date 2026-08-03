<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $correction_request_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $municipal_notification_id
 * @property string $receipt_number
 * @property array<string, mixed> $snapshot_payload
 * @property string $snapshot_hash
 * @property Carbon $submitted_at
 * @property Carbon $created_at
 * @property-read CorrectionRequest $correctionRequest
 * @property-read Application $application
 * @property-read User $candidate
 * @property-read OfficialNotification|null $municipalNotification
 */
class CorrectionSubmissionReceipt extends Model
{
    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'snapshot_payload' => 'array',
            'submitted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'Os recibos de submissão de aperfeiçoamento são imutáveis.',
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Os recibos de submissão de aperfeiçoamento não podem ser eliminados.',
            );
        });
    }

    public function getRouteKeyName(): string
    {
        return 'receipt_number';
    }

    /** @return BelongsTo<CorrectionRequest, $this> */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<OfficialNotification, $this> */
    public function municipalNotification(): BelongsTo
    {
        return $this->belongsTo(
            OfficialNotification::class,
            'municipal_notification_id',
        );
    }
}
