<?php

namespace App\Models;

use App\Enums\InspectionType;
use Database\Factories\InspectionChecklistTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InspectionChecklistTemplate extends Model
{
    /** @use HasFactory<InspectionChecklistTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'municipality_id',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'inspection_type' => InspectionType::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return HasMany<InspectionChecklistTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            InspectionChecklistTemplateItem::class,
        )->orderBy('sort_order');
    }
}
