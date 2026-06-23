<?php

namespace App\Models;

use Database\Factories\PublicPortalLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicPortalLink extends Model
{
    /** @use HasFactory<PublicPortalLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'url',
        'category',
        'description',
        'opens_new_tab',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'opens_new_tab' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<PublicPortalLink>  $query
     * @return Builder<PublicPortalLink>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
