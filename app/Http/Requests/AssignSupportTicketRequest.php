<?php

namespace App\Http\Requests;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class AssignSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('supportTicket');

        return $ticket instanceof SupportTicket && ($this->user()?->can('assign', $ticket) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'exists:users,id'],
        ];
    }
}
