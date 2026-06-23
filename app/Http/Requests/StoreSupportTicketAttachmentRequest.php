<?php

namespace App\Http\Requests;

use App\Models\SupportTicketAttachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupportTicketAttachment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attachment' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,odt,txt'],
            'support_ticket_message_id' => ['nullable', 'exists:support_ticket_messages,id'],
        ];
    }
}
