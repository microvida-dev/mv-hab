<?php

namespace App\Models;

use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use Database\Factories\CorrectionResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $correction_request_id
 * @property int $correction_request_item_id
 * @property int $application_id
 * @property int $user_id
 * @property int|null $document_submission_id
 * @property int|null $document_version_id
 * @property string|null $response_text
 * @property CorrectionResponseKind|null $response_kind
 * @property CorrectionResponseStatus $status
 * @property Carbon|null $prepared_at
 * @property Carbon|null $submitted_at
 * @property-read CorrectionRequest $correctionRequest
 * @property-read CorrectionRequestItem $correctionRequestItem
 * @property-read DocumentSubmission|null $documentSubmission
 * @property-read DocumentVersion|null $documentVersion
 */
class CorrectionResponse extends Model
{
    /** @use HasFactory<CorrectionResponseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'response_text',
        'document_submission_id',
        'document_version_id',
        'response_kind',
    ];

    protected function casts(): array
    {
        return [
            'response_kind' => CorrectionResponseKind::class,
            'status' => CorrectionResponseStatus::class,
            'review_result' => CorrectionResponseReviewResult::class,
            'prepared_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CorrectionRequest, $this> */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    /** @return BelongsTo<CorrectionRequestItem, $this> */
    public function correctionRequestItem(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequestItem::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<DocumentSubmission, $this> */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
    }

    /** @return BelongsTo<DocumentVersion, $this> */
    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
