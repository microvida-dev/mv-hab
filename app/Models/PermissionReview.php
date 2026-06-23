<?php

namespace App\Models;

use Database\Factories\PermissionReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionReview extends Model
{
    /** @use HasFactory<PermissionReviewFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'findings' => 'array',
            'recommendations' => 'array',
        ];
    }

    /**
     * @return HasMany<PermissionReviewItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PermissionReviewItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
