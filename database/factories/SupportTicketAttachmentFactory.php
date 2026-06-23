<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketAttachment>
 */
class SupportTicketAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'uploaded_by' => User::factory(),
            'filename' => fake()->uuid().'.pdf',
            'original_filename' => 'documento-ficticio.pdf',
            'storage_disk' => 'local',
            'path' => 'support-tickets/demo/documento-ficticio.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'checksum' => hash('sha256', 'demo'),
            'is_private' => true,
        ];
    }
}
