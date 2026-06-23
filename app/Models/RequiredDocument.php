<?php

namespace App\Models;

use App\Enums\DocumentAppliesTo;
use App\Enums\RequiredDocumentConditionOperator;
use Database\Factories\RequiredDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $program_id
 * @property int|null $contest_id
 * @property int $document_type_id
 * @property DocumentAppliesTo $required_for
 * @property RequiredDocumentConditionOperator $condition_operator
 * @property string $condition_key
 * @property string|null $condition_value
 * @property bool $is_required
 * @property bool $is_active
 * @property string|null $instructions
 * @property-read DocumentType|null $documentType
 */
class RequiredDocument extends Model
{
    /** @use HasFactory<RequiredDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_type_id',
        'program_id',
        'contest_id',
        'required_for',
        'condition_key',
        'condition_operator',
        'condition_value',
        'is_required',
        'is_active',
        'instructions',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required_for' => DocumentAppliesTo::class,
            'condition_operator' => RequiredDocumentConditionOperator::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<DocumentType, $this> */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return HasMany<DocumentSubmission, $this> */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }
}
