<?php

namespace App\Models;

use App\Enums\NotificationPriority;
use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationStatus;
use App\Enums\OfficialNotificationType;
use Database\Factories\OfficialNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $communication_log_id
 * @property int $user_id
 * @property OfficialNotificationType $notification_type
 * @property NotificationPriority $priority
 * @property OfficialNotificationStatus $status
 * @property string $event_code
 * @property string|null $subject
 * @property string|null $title
 * @property string|null $body
 * @property bool $requires_acknowledgement
 * @property-read User|null $user
 * @property-read Application|null $application
 * @property-read CommunicationLog|null $communication
 * @property-read Model|null $notifiable
 */
class OfficialNotification extends Model
{
    /** @use HasFactory<OfficialNotificationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['notification_type', 'channel', 'subject', 'body', 'title', 'action_url'];

    protected function casts(): array
    {
        return [
            'notification_type' => OfficialNotificationType::class,
            'status' => OfficialNotificationStatus::class,
            'channel' => OfficialNotificationChannel::class,
            'priority' => NotificationPriority::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'archived_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
            'failed_at' => 'datetime',
            'requires_acknowledgement' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return MorphTo<Model, $this> */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<CommunicationLog, $this> */
    public function communication(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class, 'communication_log_id');
    }

    /** @return HasMany<CommunicationDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(CommunicationDelivery::class);
    }
}
