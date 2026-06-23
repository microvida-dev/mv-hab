<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use App\Enums\TemplateStatus;
use App\Enums\TemplateType;
use Database\Factories\NotificationTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CommunicationChannel $channel
 * @property TemplateStatus $status
 * @property bool $is_official
 * @property bool $requires_acknowledgement
 * @property-read NotificationTemplateVersion|null $activeVersion
 */
class NotificationTemplate extends Model
{
    /** @use HasFactory<NotificationTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'active_version_id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'template_type' => TemplateType::class,
            'channel' => CommunicationChannel::class,
            'status' => TemplateStatus::class,
            'requires_acknowledgement' => 'boolean',
            'is_official' => 'boolean',
            'is_default' => 'boolean',
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

    /** @return BelongsTo<NotificationTemplateVersion, $this> */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplateVersion::class, 'active_version_id');
    }

    /** @return HasMany<NotificationTemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(NotificationTemplateVersion::class)->orderByDesc('version_number');
    }

    /** @return HasMany<NotificationEventRule, $this> */
    public function eventRules(): HasMany
    {
        return $this->hasMany(NotificationEventRule::class);
    }

    /** @return HasMany<CommunicationLog, $this> */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }
}
