<?php

namespace App\Models;

use App\Enums\TemplateStatus;
use Database\Factories\DocumentTemplateVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property TemplateStatus $status
 * @property string|null $title
 * @property string|null $body
 * @property string|null $html_body
 * @property string|null $header
 * @property string|null $footer
 * @property Carbon|null $approved_at
 * @property-read DocumentTemplate|null $template
 */
class DocumentTemplateVersion extends Model
{
    /** @use HasFactory<DocumentTemplateVersionFactory> */
    use HasFactory;

    protected $guarded = ['id', 'version_number', 'status', 'created_by', 'approved_by', 'approved_at', 'activated_at', 'archived_at'];

    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'variables_schema' => 'array',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DocumentTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /** @return HasMany<GeneratedOfficialDocument, $this> */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedOfficialDocument::class);
    }
}
