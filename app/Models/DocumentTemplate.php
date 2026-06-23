<?php

namespace App\Models;

use App\Enums\TemplateStatus;
use Database\Factories\DocumentTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property TemplateStatus $status
 * @property string|null $title
 * @property string|null $body
 * @property string|null $html_body
 * @property string|null $header
 * @property string|null $footer
 * @property-read DocumentTemplateVersion|null $activeVersion
 */
class DocumentTemplate extends Model
{
    /** @use HasFactory<DocumentTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'active_version_id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'is_official' => 'boolean',
            'is_default' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    /** @return BelongsTo<DocumentTemplateVersion, $this> */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'active_version_id');
    }

    /** @return HasMany<DocumentTemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class)->orderByDesc('version_number');
    }

    /** @return HasMany<GeneratedOfficialDocument, $this> */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedOfficialDocument::class);
    }
}
