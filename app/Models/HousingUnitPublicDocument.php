<?php

namespace App\Models;

use App\Enums\HousingUnitPublicDocumentType;
use Database\Factories\HousingUnitPublicDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property bool $is_public
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 */
class HousingUnitPublicDocument extends Model
{
    /** @use HasFactory<HousingUnitPublicDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'housing_unit_id',
        'contest_id',
        'uploaded_by',
        'approved_by',
        'title',
        'description',
        'document_type',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'checksum',
        'is_public',
        'approved_at',
        'published_at',
        'expires_at',
        'sort_order',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => HousingUnitPublicDocumentType::class,
            'is_public' => 'boolean',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'download_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<HousingUnit, $this>
     */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isDownloadable(): bool
    {
        return $this->is_public
            && ($this->published_at === null || $this->published_at->isPast())
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
