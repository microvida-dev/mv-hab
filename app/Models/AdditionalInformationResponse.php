<?php

namespace App\Models;

use App\Enums\AdditionalInformationResponseStatus;
use Database\Factories\AdditionalInformationResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdditionalInformationResponse extends Model
{
    /** @use HasFactory<AdditionalInformationResponseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['response_text', 'document_submission_id'];

    protected function casts(): array
    {
        return [
            'status' => AdditionalInformationResponseStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdditionalInformationRequest, $this>
     */
    public function additionalInformationRequest(): BelongsTo
    {
        return $this->belongsTo(AdditionalInformationRequest::class);
    }

    /**
     * @return BelongsTo<Complaint, $this>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
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
