<?php

namespace App\Models;

use App\Enums\RetentionAction;
use Database\Factories\RetentionPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property bool $requires_manual_approval
 * @property RetentionAction $retention_action
 */
class RetentionPolicy extends Model
{
    /** @use HasFactory<RetentionPolicyFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'retention_action' => RetentionAction::class,
            'requires_manual_approval' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<RetentionExecution, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(RetentionExecution::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
