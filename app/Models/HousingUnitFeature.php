<?php

namespace App\Models;

use Database\Factories\HousingUnitFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousingUnitFeature extends Model
{
    /** @use HasFactory<HousingUnitFeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'housing_unit_id',
        'key',
        'label',
        'value',
        'icon',
        'sort_order',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
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
}
