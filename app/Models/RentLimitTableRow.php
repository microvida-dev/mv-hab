<?php

namespace App\Models;

use Database\Factories\RentLimitTableRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $manifest_id
 * @property string $municipality_code
 * @property string $typology
 * @property string|null $minimum_rent
 * @property string $maximum_rent
 * @property string|null $source_row_reference
 */
class RentLimitTableRow extends Model
{
    /** @use HasFactory<RentLimitTableRowFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'minimum_rent' => 'decimal:2',
            'maximum_rent' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<RentLimitTableManifest, $this> */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(RentLimitTableManifest::class, 'manifest_id');
    }
}
