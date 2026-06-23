<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use Database\Factories\CommunicationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property CommunicationChannel $channel
 * @property CommunicationDeliveryStatus $status
 * @property string|null $destination
 * @property-read CommunicationLog|null $communication
 * @property-read OfficialNotification|null $notification
 */
class CommunicationDelivery extends Model
{
    /** @use HasFactory<CommunicationDeliveryFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'queued_at',
        'processing_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'cancelled_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'channel' => CommunicationChannel::class,
            'status' => CommunicationDeliveryStatus::class,
            'queued_at' => 'datetime',
            'processing_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CommunicationLog, $this> */
    public function communication(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class, 'communication_log_id');
    }

    /** @return BelongsTo<OfficialNotification, $this> */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(OfficialNotification::class, 'official_notification_id');
    }

    /** @return HasMany<CommunicationAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(CommunicationAttempt::class)->orderBy('attempt_number');
    }

    /** @return HasMany<CommunicationReceipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(CommunicationReceipt::class);
    }
}
