<?php

namespace App\Models;

use App\Enums\TemplateStatus;
use Database\Factories\NotificationTemplateVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TemplateStatus $status
 * @property string $subject
 * @property string|null $title
 * @property string $body
 * @property string|null $html_body
 * @property string|null $sms_body
 */
class NotificationTemplateVersion extends Model
{
    /** @use HasFactory<NotificationTemplateVersionFactory> */
    use HasFactory;

    protected $guarded = ['id', 'version_number', 'status', 'created_by', 'approved_by', 'approved_at', 'activated_at', 'archived_at'];

    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'variables_schema' => 'array',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<NotificationTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<CommunicationLog, $this> */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }
}
