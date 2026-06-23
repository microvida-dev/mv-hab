<?php

namespace App\Models;

use App\Enums\DocumentAiScoreLabel;
use Database\Factories\DocumentAiScoreFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $document_ai_analysis_id
 * @property int|null $application_id
 * @property int $score
 * @property DocumentAiScoreLabel $label
 * @property bool $requires_manual_review
 * @property array<string, mixed>|null $components
 * @property array<string, mixed>|null $explanation
 * @property-read DocumentAiAnalysis $analysis
 */
class DocumentAiScore extends Model
{
    /** @use HasFactory<DocumentAiScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'summary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'label' => DocumentAiScoreLabel::class,
            'components' => 'array',
            'explanation' => 'array',
            'requires_manual_review' => 'boolean',
            'calculated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DocumentAiAnalysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
    }

    /** @return BelongsTo<DocumentSubmission, $this> */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return HasMany<DocumentAiSuggestion, $this> */
    public function suggestions(): HasMany
    {
        return $this->hasMany(DocumentAiSuggestion::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRequiresReview(Builder $query): Builder
    {
        return $query->where('requires_manual_review', true);
    }
}
