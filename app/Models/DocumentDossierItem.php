<?php

namespace App\Models;

use App\Enums\DocumentDossierItemStatus;
use Database\Factories\DocumentDossierItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'is_required' => 'boolean',
            'is_missing' => 'boolean',
            'is_rejected' => 'boolean',
            'is_expired' => 'boolean',
            'is_validated' => 'boolean',
        ];
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
