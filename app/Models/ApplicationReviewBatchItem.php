<?php

namespace App\Models;

use App\Enums\ApplicationReviewBatchOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $application_review_batch_id
 * @property int $administrative_process_id
 * @property int $application_id
 * @property int|null $application_review_id
 * @property string $process_number
 * @property string|null $application_number
 * @property string $application_public_id
 * @property ApplicationReviewBatchOutcome $outcome
 * @property string|null $technical_result
 * @property int|null $review_lock_version
 * @property array<string, mixed> $readiness_snapshot
 * @property array<int, array<string, mixed>> $document_snapshot
 * @property array<string, mixed> $snapshot_payload
 * @property string $source_fingerprint
 * @property string $snapshot_hash
 */
class ApplicationReviewBatchItem extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException(
                'Um item de snapshot de revisão não pode ser alterado.',
            );
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Um item de snapshot de revisão não pode ser eliminado.',
            );
        });
    }

    protected function casts(): array
    {
        return [
            'outcome' => ApplicationReviewBatchOutcome::class,
            'review_lock_version' => 'integer',
            'readiness_snapshot' => 'array',
            'document_snapshot' => 'array',
            'snapshot_payload' => 'array',
        ];
    }

    /** @return BelongsTo<ApplicationReviewBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationReviewBatch::class,
            'application_review_batch_id',
        );
    }

    /** @return BelongsTo<AdministrativeProcess, $this> */
    public function administrativeProcess(): BelongsTo
    {
        return $this->belongsTo(AdministrativeProcess::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<ApplicationReview, $this> */
    public function applicationReview(): BelongsTo
    {
        return $this->belongsTo(ApplicationReview::class);
    }
}
