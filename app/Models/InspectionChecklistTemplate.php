<?php

namespace App\Models;

use App\Enums\InspectionType;
use Database\Factories\InspectionChecklistTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InspectionChecklistTemplate extends Model
{
    /** @use HasFactory<InspectionChecklistTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'inspection_type' => InspectionType::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<InspectionChecklistTemplateItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InspectionChecklistTemplateItem::class)->orderBy('sort_order');
    }
}
