<?php

namespace App\Models;

use App\Enums\MaintenanceAttachmentType;
use Database\Factories\PropertyInspectionAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyInspectionAttachment extends Model
{
    /** @use HasFactory<PropertyInspectionAttachmentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attachment_type' => MaintenanceAttachmentType::class,
            'visible_to_tenant' => 'boolean',
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
     * @return BelongsTo<PropertyInspectionItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(PropertyInspectionItem::class, 'property_inspection_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
