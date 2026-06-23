<?php

namespace App\Models;

use App\Enums\InspectionCondition;
use Database\Factories\PropertyInspectionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyInspectionItem extends Model
{
    /** @use HasFactory<PropertyInspectionItemFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'condition' => InspectionCondition::class,
            'requires_maintenance' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PropertyInspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(PropertyInspection::class, 'property_inspection_id');
    }

    /**
     * @return BelongsTo<InspectionChecklistTemplateItem, $this>
     */
    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklistTemplateItem::class, 'inspection_checklist_template_item_id');
    }

    /**
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }
}
