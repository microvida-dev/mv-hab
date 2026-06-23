<?php

namespace App\Models;

use App\Enums\SecurityAlertSeverity;
use App\Enums\SecurityAlertStatus;
use Database\Factories\SecurityAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property SecurityAlertSeverity $severity
 */
class SecurityAlert extends Model
{
    /** @use HasFactory<SecurityAlertFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => SecurityAlertStatus::class,
            'severity' => SecurityAlertSeverity::class,
            'detected_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SecurityAlertRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(SecurityAlertRule::class, 'security_alert_rule_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
