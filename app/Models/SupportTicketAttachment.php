<?php

namespace App\Models;

use Database\Factories\SupportTicketAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicketAttachment extends Model
{
    /** @use HasFactory<SupportTicketAttachmentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'path', 'checksum', 'storage_disk', 'uploaded_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * @return BelongsTo<SupportTicketMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportTicketMessage::class, 'support_ticket_message_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
