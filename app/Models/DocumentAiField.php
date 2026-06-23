<?php

namespace App\Models;

use Database\Factories\DocumentAiFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_ai_analysis_id
 * @property string $key
 * @property string|null $label
 * @property string|null $value
 * @property string|null $normalized_value
 * @property string|null $source
 * @property numeric-string|float|int|null $confidence
 * @property array<string, mixed>|null $metadata
 * @property bool $requires_review
 */
class DocumentAiField extends Model
{
    /** @use HasFactory<DocumentAiFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'value',
        'normalized_value',
        'value_type',
        'confidence',
        'document_type',
        'source',
        'requires_review',
        'page',
        'bbox',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'requires_review' => 'boolean',
            'page' => 'integer',
            'bbox' => 'array',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<DocumentAiAnalysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
    }
}
