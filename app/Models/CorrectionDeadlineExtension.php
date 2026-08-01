<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $correction_request_id
 * @property Carbon $original_deadline_at
 * @property Carbon $previous_deadline_at
 * @property Carbon $extended_deadline_at
 * @property string $reason
 * @property int $authorized_by
 * @property Carbon $authorized_at
 * @property-read CorrectionRequest $correctionRequest
 * @property-read User $authorizedBy
 */
class CorrectionDeadlineExtension extends Model
{
    protected $guarded = [
        'id',
        'correction_request_id',
        'original_deadline_at',
        'previous_deadline_at',
        'extended_deadline_at',
        'authorized_by',
        'authorized_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'original_deadline_at' => 'datetime',
            'previous_deadline_at' => 'datetime',
            'extended_deadline_at' => 'datetime',
            'authorized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'O histórico de prorrogações é imutável.',
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'O histórico de prorrogações não pode ser eliminado.',
            );
        });
    }

    /** @return BelongsTo<CorrectionRequest, $this> */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
