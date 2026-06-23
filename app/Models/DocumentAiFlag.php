<?php

namespace App\Models;

use Database\Factories\DocumentAiFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_ai_analysis_id
 */
class DocumentAiFlag extends Model
{
    /** @use HasFactory<DocumentAiFlagFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'severity',
        'message',
        'score_impact',
        'suggestion_template',
        'detected_by',
        'confidence',
        'details',
        'requires_manual_review',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score_impact' => 'integer',
            'confidence' => 'decimal:2',
            'details' => 'array',
            'requires_manual_review' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DocumentAiAnalysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
