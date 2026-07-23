<?php

namespace App\Models;

use App\Enums\FeatureKey;
use Database\Factories\MunicipalityFeatureEntitlementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalityFeatureEntitlement extends Model
{
    /** @use HasFactory<MunicipalityFeatureEntitlementFactory> */
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'feature_key',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'feature_key' => FeatureKey::class,
            'enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<Municipality, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForMunicipality(Builder $query, Municipality $municipality): Builder
    {
        return $query->where('municipality_id', $municipality->getKey());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForFeature(Builder $query, FeatureKey $feature): Builder
    {
        return $query->where('feature_key', $feature->value);
    }
}
