<?php

namespace App\Models;

use Database\Factories\DocumentAiProcessingLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_ai_analysis_id
 */
class DocumentAiProcessingLog extends Model
{
    /** @use HasFactory<DocumentAiProcessingLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'step',
        'level',
        'message',
        'context',
        'duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DocumentAiAnalysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
    }
}
