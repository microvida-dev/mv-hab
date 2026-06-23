<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use App\Enums\NotificationPriority;
use Database\Factories\NotificationEventRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $event_code
 * @property CommunicationChannel $channel
 * @property NotificationPriority $priority
 * @property bool $requires_acknowledgement
 * @property bool $send_immediately
 * @property int $delay_minutes
 * @property-read NotificationTemplate|null $template
 */
class NotificationEventRule extends Model
{
    /** @use HasFactory<NotificationEventRuleFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'channel' => CommunicationChannel::class,
            'priority' => NotificationPriority::class,
            'is_active' => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'send_immediately' => 'boolean',
        ];
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<NotificationTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }
}
