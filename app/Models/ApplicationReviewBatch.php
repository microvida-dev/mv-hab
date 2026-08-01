<?php

namespace App\Models;

use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $municipality_id
 * @property int $contest_id
 * @property int|null $correction_request_id
 * @property string|null $collective_scope_key
 * @property ApplicationReviewBatchCycle $cycle
 * @property int $sequence_number
 * @property ApplicationReviewBatchStatus $status
 * @property string|null $reason
 * @property int $item_count
 * @property string $seal_key
 * @property string $source_fingerprint
 * @property string $snapshot_hash
 * @property int|null $sealed_by
 * @property Carbon $sealed_at
 * @property-read Municipality $municipality
 * @property-read Contest $contest
 * @property-read CorrectionRequest|null $correctionRequest
 * @property-read User|null $sealedBy
 */
class ApplicationReviewBatch extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /** @var list<string> */
    private const IMMUTABLE_COLUMNS = [
        'public_id',
        'municipality_id',
        'contest_id',
        'correction_request_id',
        'collective_scope_key',
        'cycle',
        'sequence_number',
        'reason',
        'item_count',
        'seal_key',
        'source_fingerprint',
        'snapshot_hash',
        'sealed_by',
        'sealed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $batch): void {
            if (trim((string) $batch->public_id) === '') {
                $batch->public_id = (string) Str::orderedUuid();
            }

            if (
                $batch->correction_request_id === null
                && trim((string) $batch->collective_scope_key) === ''
            ) {
                $batch->collective_scope_key = hash(
                    'sha256',
                    'contest:'.$batch->contest_id
                        .':cycle:'.$batch->cycle->value,
                );
            }
        });

        static::updating(function (self $batch): void {
            if ($batch->isDirty(self::IMMUTABLE_COLUMNS)) {
                throw new LogicException(
                    'Um lote de revisão selado não pode alterar o seu conteúdo imutável.',
                );
            }
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Um lote de revisão selado não pode ser eliminado.',
            );
        });
    }

    protected function casts(): array
    {
        return [
            'cycle' => ApplicationReviewBatchCycle::class,
            'status' => ApplicationReviewBatchStatus::class,
            'sequence_number' => 'integer',
            'item_count' => 'integer',
            'sealed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<CorrectionRequest, $this> */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sealedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sealed_by');
    }

    /** @return HasMany<ApplicationReviewBatchItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ApplicationReviewBatchItem::class)
            ->orderBy('process_number')
            ->orderBy('id');
    }

    /** @return HasOne<ApplicationReviewPublication, $this> */
    public function publication(): HasOne
    {
        return $this->hasOne(
            ApplicationReviewPublication::class,
            'application_review_batch_id',
        );
    }
}
