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

        if (! $ticket instanceof SupportTicket) {
            return false;
        }

        return $this->routeIs('backoffice.support-ticket-messages.store')
            ? $this->user()?->can(
                'messageBackoffice',
                $ticket,
            ) === true
            : $this->user()?->can('update', $ticket) === true;
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
