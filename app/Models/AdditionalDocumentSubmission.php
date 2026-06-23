<?php

namespace App\Models;

use App\Enums\AdditionalDocumentStatus;
use Database\Factories\AdditionalDocumentSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property AdditionalDocumentStatus $status
 * @property-read Application|null $application
 */
class AdditionalDocumentSubmission extends Model
{
    /** @use HasFactory<AdditionalDocumentSubmissionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['status', 'title', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AdditionalDocumentStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AdditionalDocumentRequest, $this> */
    public function additionalDocumentRequest(): BelongsTo
    {
        return $this->belongsTo(AdditionalDocumentRequest::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
