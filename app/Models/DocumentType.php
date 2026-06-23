<?php

namespace App\Models;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property DocumentCategory|null $category
 * @property array<int, mixed>|null $allowed_mime_types
 * @property int|null $max_file_size_mb
 */
class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'applies_to',
        'is_active',
        'is_required_by_default',
        'requires_expiry_date',
        'requires_issue_date',
        'allowed_mime_types',
        'max_file_size_mb',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'applies_to' => DocumentAppliesTo::class,
            'is_active' => 'boolean',
            'is_required_by_default' => 'boolean',
            'requires_expiry_date' => 'boolean',
            'requires_issue_date' => 'boolean',
            'allowed_mime_types' => 'array',
            'max_file_size_mb' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<RequiredDocument, $this>
     */
    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(RequiredDocument::class);
    }

    /**
     * @return HasMany<DocumentSubmission, $this>
     */
    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    /**
     * @return list<string>
     */
    public function allowedMimeTypes(): array
    {
        $mimeTypes = $this->allowed_mime_types;

        if (! is_array($mimeTypes) || $mimeTypes === []) {
            return [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/heic',
                'image/heif',
            ];
        }

        return array_values(
            array_filter(
                $mimeTypes,
                static fn (mixed $value): bool => is_string($value)
            )
        );
    }

    public function maxFileSizeKilobytes(): int
    {
        return max(1, (int) $this->max_file_size_mb) * 1024;
    }
}
