<?php

namespace App\Models;

use Database\Factories\PermissionReviewItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionReviewItem extends Model
{
    /** @use HasFactory<PermissionReviewItemFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<PermissionReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(PermissionReview::class, 'permission_review_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
