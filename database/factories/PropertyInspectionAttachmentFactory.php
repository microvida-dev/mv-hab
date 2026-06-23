<?php

namespace Database\Factories;

use App\Enums\MaintenanceAttachmentType;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyInspectionAttachment>
 */
class PropertyInspectionAttachmentFactory extends Factory
{
    protected $model = PropertyInspectionAttachment::class;

    public function definition(): array
    {
        return [
            'property_inspection_id' => PropertyInspection::factory(),
            'attachment_type' => MaintenanceAttachmentType::Photo,
            'original_filename' => 'vistoria-demo.jpg',
            'storage_disk' => 'local',
            'storage_path' => 'inspections/demo/vistoria-demo.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'visible_to_tenant' => true,
        ];
    }
}
