<?php

namespace App\Models;

use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewPublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $municipality_id
 * @property int $contest_id
 * @property int $application_review_batch_id
 * @property ApplicationReviewBatchCycle $cycle
 * @property int $sequence_number
 * @property ApplicationReviewPublicationStatus $status
 * @property string $reason
 * @property int $item_count
 * @property string $publication_key
 * @property string $source_snapshot_hash
 * @property string $publication_hash
 * @property int|null $published_by
 * @property Carbon $published_at
 * @property-read Municipality $municipality
 * @property-read Contest $contest
 * @property-read ApplicationReviewBatch $batch
 * @property-read User|null $publishedBy
 */
class ApplicationReviewPublication extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /** @var list<string> */
    private const IMMUTABLE_COLUMNS = [
        'public_id',
        'municipality_id',
        'contest_id',
        'application_review_batch_id',
        'cycle',
        'sequence_number',
        'status',
        'reason',
        'item_count',
        'publication_key',
        'source_snapshot_hash',
        'publication_hash',
        'published_by',
        'published_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $publication): void {
            if (trim((string) $publication->public_id) === '') {
                $publication->public_id = (string) Str::orderedUuid();
            }
        });

        static::updating(function (self $publication): void {
            if ($publication->isDirty(self::IMMUTABLE_COLUMNS)) {
                throw new LogicException(
                    'Uma publicação de revisão não pode ser alterada.',
                );
            }
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Uma publicação de revisão não pode ser eliminada.',
            );
        });
    }

    protected function casts(): array
    {
        return [
            'cycle' => ApplicationReviewBatchCycle::class,
            'status' => ApplicationReviewPublicationStatus::class,
            'sequence_number' => 'integer',
            'item_count' => 'integer',
            'published_at' => 'datetime',
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

    /** @return BelongsTo<ApplicationReviewBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationReviewBatch::class,
            'application_review_batch_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return HasMany<ApplicationReviewPublicationResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(ApplicationReviewPublicationResult::class)
            ->orderBy('process_number')
            ->orderBy('id');
    }
}
