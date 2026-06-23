<?php

namespace App\Http\Requests;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupportTicket::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'exists:applications,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
            'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
            'category' => ['required', 'string', Rule::in(TicketCategory::values())],
            'priority' => ['nullable', 'string', Rule::in(TicketPriority::values())],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'context' => ['nullable', 'array'],
        ];
    }
}
