<?php

namespace App\Models;

use App\Enums\InternalAlertSeverity;
use App\Enums\InternalAlertStatus;
use App\Enums\InternalAlertType;
use Database\Factories\InternalAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalAlert extends Model
{
    /** @use HasFactory<InternalAlertFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'message',
        'assigned_role',
        'due_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => InternalAlertType::class,
            'severity' => InternalAlertSeverity::class,
            'status' => InternalAlertStatus::class,
            'due_at' => 'datetime',
            'seen_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'alert_number';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
