<?php

namespace App\Models;

use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequiredAction;
use Database\Factories\CorrectionRequestItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorrectionRequestItem extends Model
{
    /** @use HasFactory<CorrectionRequestItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'target_type',
        'target_id',
        'issue_type',
        'title',
        'description',
        'required_action',
        'is_required',
        'document_type_id',
        'required_document_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'issue_type' => CorrectionIssueType::class,
            'required_action' => CorrectionRequiredAction::class,
            'status' => CorrectionRequestItemStatus::class,
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CorrectionRequest, $this>
     */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<RequiredDocument, $this>
     */
    public function requiredDocument(): BelongsTo
    {
        return $this->belongsTo(RequiredDocument::class);
    }

    /**
     * @return HasMany<CorrectionResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(CorrectionResponse::class);
    }
}
