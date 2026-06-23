<?php

namespace App\Http\Requests;

use App\Enums\MessageVisibility;
use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:1', 'max:10000'],
            'visibility' => ['nullable', 'string', Rule::in(MessageVisibility::values())],
        ];
    }
}
