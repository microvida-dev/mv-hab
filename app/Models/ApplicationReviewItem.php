<?php

namespace App\Models;

use App\Enums\ApplicationReviewResult;
use Database\Factories\ApplicationReviewItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApplicationReviewItem extends Model
{
    /** @use HasFactory<ApplicationReviewItemFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'target_type',
        'target_id',
        'result',
        'message',
        'technical_message',
        'requires_correction',
        'correction_reason',
    ];

    protected function casts(): array
    {
        return [
            'result' => ApplicationReviewResult::class,
            'requires_correction' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ApplicationReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(ApplicationReview::class, 'application_review_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
