<?php

namespace App\Models;

use App\Enums\AnnualDocumentSubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualDocumentUpdateSubmission extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'status', 'submitted_at', 'reviewed_at', 'reviewed_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => AnnualDocumentSubmissionStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AnnualDocumentUpdateRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AnnualDocumentUpdateRequest::class, 'annual_document_update_request_id');
    }

    /**
     * @return BelongsTo<DocumentSubmission, $this>
     */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
    }
}
