<?php

namespace App\Models;

use App\Enums\CommunicationStatus;
use App\Enums\NotificationPriority;
use Database\Factories\CommunicationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property CommunicationStatus $status
 * @property NotificationPriority $priority
 * @property string $event_code
 * @property string|null $subject
 * @property string|null $title
 * @property string|null $body_snapshot
 * @property bool $requires_acknowledgement
 * @property Carbon|null $sent_at
 * @property Carbon|null $failed_at
 */
class CommunicationLog extends Model
{
    /** @use HasFactory<CommunicationLogFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'communication_number',
        'status',
        'created_by',
        'queued_at',
        'sent_at',
        'failed_at',
        'cancelled_at',
        'archived_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommunicationStatus::class,
            'priority' => NotificationPriority::class,
            'is_official' => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /** @return MorphTo<Model, $this> */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<NotificationTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    /** @return BelongsTo<NotificationTemplateVersion, $this> */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplateVersion::class, 'notification_template_version_id');
    }

    /** @return HasMany<CommunicationDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(CommunicationDelivery::class);
    }

    /** @return HasMany<OfficialNotification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(OfficialNotification::class);
    }

    /** @return HasMany<CommunicationReceipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(CommunicationReceipt::class);
    }
}
