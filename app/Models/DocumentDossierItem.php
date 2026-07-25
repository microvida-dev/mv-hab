<?php

namespace App\Models;

use App\Enums\DocumentDossierItemStatus;
use Database\Factories\DocumentDossierItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string|null $target_label
 * @property int $requirement_instance
 * @property int $required_submissions
 * @property Carbon|null $reference_period
 */
class DocumentDossierItem extends Model
{
    /** @use HasFactory<DocumentDossierItemFactory> */
    use HasFactory;

    protected $fillable = [
        'category',
        'label',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentDossierItemStatus::class,
            'sort_order' => 'integer',
            'target_id' => 'integer',
            'requirement_instance' => 'integer',
            'required_submissions' => 'integer',
            'reference_period' => 'date',
            'is_required' => 'boolean',
            'is_missing' => 'boolean',
            'is_rejected' => 'boolean',
            'is_expired' => 'boolean',
            'is_validated' => 'boolean',
        ];
    }

    public function positionLabel(): ?string
    {
        if ($this->required_submissions <= 1) {
            return null;
        }

        return $this->requirement_instance
            .'/'
            .$this->required_submissions;
    }

    public function referencePeriodLabel(): ?string
    {
        return $this->reference_period?->format('m/Y');
    }

    /** @return BelongsTo<DocumentDossier, $this> */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DocumentDossier::class, 'document_dossier_id');
    }

    /** @return BelongsTo<DocumentSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class, 'document_submission_id');
    }

    /** @return BelongsTo<RequiredDocument, $this> */
    public function requiredDocument(): BelongsTo
    {
        return $this->belongsTo(RequiredDocument::class);
    }

    /** @return BelongsTo<DocumentType, $this> */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
