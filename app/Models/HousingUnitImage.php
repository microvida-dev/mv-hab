<?php

namespace App\Models;

use Database\Factories\HousingUnitImageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HousingUnitImage extends Model
{
    /** @use HasFactory<HousingUnitImageFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'housing_unit_id',
        'uploaded_by',
        'approved_by',
        'title',
        'alt_text',
        'disk',
        'path',
        'thumbnail_path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'is_cover',
        'is_public',
        'approved_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'is_cover' => 'boolean',
            'is_public' => 'boolean',
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
        return $query->where('is_public', true);
    }
}
