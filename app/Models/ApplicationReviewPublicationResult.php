<?php

namespace App\Models;

use App\Enums\ApplicationReviewBatchOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $application_review_publication_id
 * @property int $application_review_batch_item_id
 * @property int $municipality_id
 * @property int $contest_id
 * @property int $administrative_process_id
 * @property int $application_id
 * @property int $user_id
 * @property string $process_number
 * @property string|null $application_number
 * @property string $application_public_id
 * @property ApplicationReviewBatchOutcome $outcome
 * @property string|null $technical_result
 * @property array<string, mixed> $result_payload
 * @property string $source_snapshot_hash
 * @property string $result_hash
 * @property string $notification_hash
 * @property int $official_notification_id
 * @property int $communication_log_id
 * @property int $in_app_delivery_id
 * @property int $email_delivery_id
 * @property Carbon $published_at
 * @property-read ApplicationReviewPublication $publication
 * @property-read Application $application
 * @property-read User $user
 * @property-read CommunicationDelivery $inAppDelivery
 * @property-read CommunicationDelivery $emailDelivery
 */
class ApplicationReviewPublicationResult extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        static::creating(function (self $result): void {
            if (trim((string) $result->public_id) === '') {
                $result->public_id = (string) Str::orderedUuid();
            }
        });

        static::updating(function (): never {
            throw new LogicException(
                'Um resultado publicado de revisão não pode ser alterado.',
            );
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Um resultado publicado de revisão não pode ser eliminado.',
            );
        });
    }

    protected function casts(): array
    {
        return [
            'outcome' => ApplicationReviewBatchOutcome::class,
            'result_payload' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<ApplicationReviewPublication, $this> */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationReviewPublication::class,
            'application_review_publication_id',
        );
    }

    /** @return BelongsTo<ApplicationReviewBatchItem, $this> */
    public function batchItem(): BelongsTo
    {
        return $this->belongsTo(
            ApplicationReviewBatchItem::class,
            'application_review_batch_item_id',
        );
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<OfficialNotification, $this> */
    public function officialNotification(): BelongsTo
    {
        return $this->belongsTo(OfficialNotification::class);
    }

    /** @return BelongsTo<CommunicationLog, $this> */
    public function communication(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class, 'communication_log_id');
    }

    /** @return BelongsTo<CommunicationDelivery, $this> */
    public function inAppDelivery(): BelongsTo
    {
        return $this->belongsTo(CommunicationDelivery::class, 'in_app_delivery_id');
    }

    /** @return BelongsTo<CommunicationDelivery, $this> */
    public function emailDelivery(): BelongsTo
    {
        return $this->belongsTo(CommunicationDelivery::class, 'email_delivery_id');
    }

    /** @return HasOne<CorrectionRequest, $this> */
    public function correctionRequest(): HasOne
    {
        return $this->hasOne(
            CorrectionRequest::class,
            'application_review_publication_result_id',
        );
    }
}
