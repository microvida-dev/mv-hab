<?php

namespace App\Models;

use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiSuggestionStatus;
use Database\Factories\DocumentAiSuggestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_ai_analysis_id
 * @property string $flag_code
 * @property DocumentAiRiskSeverity $severity
 * @property DocumentAiSuggestionStatus $status
 * @property string $suggestion
 * @property array<string, mixed>|null $metadata
 */
class DocumentAiSuggestion extends Model
{
    /** @use HasFactory<DocumentAiSuggestionFactory> */
    use HasFactory;

    protected $fillable = [
        'suggestion',
        'dismiss_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'severity' => DocumentAiRiskSeverity::class,
            'status' => DocumentAiSuggestionStatus::class,
            'metadata' => 'array',
            'accepted_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DocumentAiAnalysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
    }

    /** @return BelongsTo<DocumentAiScore, $this> */
    public function score(): BelongsTo
    {
        return $this->belongsTo(DocumentAiScore::class, 'document_ai_score_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            DocumentAiSuggestionStatus::Draft->value,
            DocumentAiSuggestionStatus::Edited->value,
            DocumentAiSuggestionStatus::Accepted->value,
        ]);
    }
}
