<?php

namespace App\Models;

use Database\Factories\WorkTaskHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskHistory extends Model
{
    /** @use HasFactory<WorkTaskHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'work_task_id',
        'event_code',
        'actor_id',
        'from_status',
        'to_status',
        'from_team_id',
        'to_team_id',
        'from_user_id',
        'to_user_id',
        'note',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkTask, $this> */
    public function workTask(): BelongsTo
    {
        return $this->belongsTo(WorkTask::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}
