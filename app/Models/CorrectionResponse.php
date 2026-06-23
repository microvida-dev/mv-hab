<?php

namespace App\Models;

use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use Database\Factories\CorrectionResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorrectionResponse extends Model
{
    /** @use HasFactory<CorrectionResponseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'response_text',
        'document_submission_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CorrectionResponseStatus::class,
            'review_result' => CorrectionResponseReviewResult::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CorrectionRequest, $this>
     */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    /**
     * @return BelongsTo<CorrectionRequestItem, $this>
     */
    public function correctionRequestItem(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequestItem::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<DocumentSubmission, $this>
     */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
