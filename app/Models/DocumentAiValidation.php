<?php

namespace App\Models;

use App\Enums\DocumentAiComparisonMethod;
use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use Database\Factories\DocumentAiValidationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_ai_analysis_id
 * @property int|null $application_id
 * @property DocumentAiValidationGroup $validation_group
 * @property string $validation_key
 * @property DocumentAiValidationStatus $status
 * @property DocumentAiValidationSeverity|null $severity
 * @property bool $requires_manual_review
 * @property array<string, mixed>|null $metadata
 * @property-read DocumentAiAnalysis $analysis
 */
class DocumentAiValidation extends Model
{
    /** @use HasFactory<DocumentAiValidationFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'message',
        'recommendation',
        'review_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validation_group' => DocumentAiValidationGroup::class,
            'status' => DocumentAiValidationStatus::class,
            'severity' => DocumentAiValidationSeverity::class,
            'confidence' => 'decimal:2',
            'comparison_method' => DocumentAiComparisonMethod::class,
            'requires_manual_review' => 'boolean',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<DocumentAiValidationRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(DocumentAiValidationRun::class, 'document_ai_validation_run_id');
    }

    /** @return BelongsTo<DocumentAiAnalysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
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

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRequiresReview(Builder $query): Builder
    {
        return $query->where('requires_manual_review', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', DocumentAiValidationSeverity::Critical->value);
    }
}
