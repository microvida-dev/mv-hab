<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\ApplicationDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    /** @use HasFactory<ApplicationDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'document_submission_id',
        'document_type_id',
        'is_required',
        'status_at_submission',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'status_at_submission' => DocumentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<DocumentSubmission, $this>
     */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
