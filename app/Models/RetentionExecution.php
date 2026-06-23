<?php

namespace App\Models;

use App\Enums\RetentionExecutionStatus;
use Database\Factories\RetentionExecutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property RetentionExecutionStatus $status
 */
class RetentionExecution extends Model
{
    /** @use HasFactory<RetentionExecutionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RetentionExecutionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    /**
     * @return BelongsTo<RetentionPolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id');
    }
}
