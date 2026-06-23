<?php

namespace Database\Factories;

use App\Enums\MaintenanceAttachmentType;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceAttachment>
 */
class MaintenanceAttachmentFactory extends Factory
{
    protected $model = MaintenanceAttachment::class;

    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'attachment_type' => MaintenanceAttachmentType::Photo,
            'original_filename' => 'imagem-demo.jpg',
            'storage_disk' => 'local',
            'storage_path' => 'maintenance/demo/imagem-demo.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'visible_to_tenant' => true,
        ];
    }
}
