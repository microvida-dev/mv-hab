<?php

namespace App\Models;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiStatus;
use Database\Factories\DocumentAiAnalysisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $document_submission_id
 * @property int|null $document_version_id
 * @property DocumentAiStatus $status
 * @property string|null $source_disk
 * @property string|null $source_path
 * @property string|null $source_mime
 * @property int|null $source_size_bytes
 * @property string|null $source_sha256
 * @property array<string, mixed>|null $raw_ai_json
 * @property DocumentAiOcrStatus|null $ocr_status
 * @property bool $ocr_available
 * @property string|null $ocr_text
 * @property numeric-string|float|int|null $ocr_quality_score
 * @property DocumentAiClassificationStatus|null $classification_status
 * @property DocumentAiDocumentType|null $detected_document_type
 * @property array<string, mixed>|null $classification_signals
 * @property bool $classification_requires_manual_review
 * @property DocumentAiExtractionStatus|null $extraction_status
 * @property array<string, mixed>|null $extraction_json
 * @property bool $extraction_requires_manual_review
 * @property-read DocumentSubmission|null $documentSubmission
 * @property-read DocumentVersion|null $documentVersion
 */
class DocumentAiAnalysis extends Model
{
    /** @use HasFactory<DocumentAiAnalysisFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'engine',
        'model',
        'summary',
        'confidence',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentAiStatus::class,
            'source_size_bytes' => 'integer',
            'raw_ai_json' => 'array',
            'confidence' => 'decimal:2',
            'ocr_status' => DocumentAiOcrStatus::class,
            'ocr_available' => 'boolean',
            'ocr_quality_score' => 'decimal:2',
            'ocr_pages_count' => 'integer',
            'ocr_processed_at' => 'datetime',
            'classification_status' => DocumentAiClassificationStatus::class,
            'detected_document_type' => DocumentAiDocumentType::class,
            'classification_confidence' => 'decimal:2',
            'classification_signals' => 'array',
            'classification_requires_manual_review' => 'boolean',
            'classified_at' => 'datetime',
            'extraction_status' => DocumentAiExtractionStatus::class,
            'extraction_json' => 'array',
            'extraction_confidence' => 'decimal:2',
            'extraction_started_at' => 'datetime',
            'extraction_completed_at' => 'datetime',
            'extraction_failed_at' => 'datetime',
            'extraction_requires_manual_review' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'manual_review_at' => 'datetime',
        ];
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

    /** @return MorphTo<Model, $this> */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<DocumentAiField, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(DocumentAiField::class);
    }

    /** @return HasMany<DocumentAiFlag, $this> */
    public function flags(): HasMany
    {
        return $this->hasMany(DocumentAiFlag::class);
    }

    /** @return HasMany<DocumentAiProcessingLog, $this> */
    public function processingLogs(): HasMany
    {
        return $this->hasMany(DocumentAiProcessingLog::class);
    }

    /** @return HasMany<DocumentAiValidation, $this> */
    public function validations(): HasMany
    {
        return $this->hasMany(DocumentAiValidation::class);
    }

    /** @return HasOne<DocumentAiScore, $this> */
    public function score(): HasOne
    {
        return $this->hasOne(DocumentAiScore::class);
    }

    /** @return HasOne<DocumentAiScore, $this> */
    public function latestScore(): HasOne
    {
        return $this->hasOne(DocumentAiScore::class)->latestOfMany();
    }

    /** @return HasMany<DocumentAiSuggestion, $this> */
    public function suggestions(): HasMany
    {
        return $this->hasMany(DocumentAiSuggestion::class);
    }
}
