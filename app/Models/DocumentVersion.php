<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $original_filename
 * @property string|null $mime_type
 */
class DocumentVersion extends Model
{
    /** @use HasFactory<DocumentVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
            'status_at_upload' => DocumentStatus::class,
        ];
    }

    /** @return BelongsTo<DocumentSubmission, $this> */
    public function documentSubmission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return HasMany<DocumentAiAnalysis, $this> */
    public function documentAiAnalyses(): HasMany
    {
        return $this->hasMany(DocumentAiAnalysis::class);
    }
}
