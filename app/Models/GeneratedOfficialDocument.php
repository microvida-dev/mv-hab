<?php

namespace App\Models;

use App\Enums\DocumentGenerationStatus;
use Database\Factories\GeneratedOfficialDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedOfficialDocument extends Model
{
    /** @use HasFactory<GeneratedOfficialDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'document_number',
        'status',
        'html_content',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_size',
        'checksum',
        'generated_by',
        'generated_at',
        'issued_by',
        'issued_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentGenerationStatus::class,
            'generated_at' => 'datetime',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /**
     * @return BelongsTo<DocumentTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'document_template_version_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
