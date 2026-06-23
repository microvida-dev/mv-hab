<?php

namespace App\Http\Requests;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('supportTicket');

        return $ticket instanceof SupportTicket && ($this->user()?->can('update', $ticket) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', Rule::in(TicketCategory::values())],
            'priority' => ['nullable', 'string', Rule::in(TicketPriority::values())],
            'subject' => ['nullable', 'string', 'max:180'],
        ];
    }
}
