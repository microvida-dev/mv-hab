<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('supportTicket');

        return $ticket instanceof SupportTicket && ($this->user()?->can('resolve', $ticket) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(TicketStatus::values())],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
