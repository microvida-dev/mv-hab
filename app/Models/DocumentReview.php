<?php

namespace App\Models;

use App\Enums\DocumentReviewDecision;
use App\Enums\DocumentStatus;
use Database\Factories\DocumentReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReview extends Model
{
    /** @use HasFactory<DocumentReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'reason',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => DocumentStatus::class,
            'to_status' => DocumentStatus::class,
            'decision' => DocumentReviewDecision::class,
        ];
    }

    /** @return BelongsTo<DocumentSubmission, $this> */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
