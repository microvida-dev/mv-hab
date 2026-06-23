<?php

namespace App\Http\Requests;

use App\Enums\CommunicationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_user_id' => ['required', 'exists:users,id'],
            'event_code' => ['required', 'string', 'max:150'],
            'channel' => ['required', Rule::in(CommunicationChannel::values())],
            'subject' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'priority' => ['required', Rule::in(NotificationPriority::values())],
            'requires_acknowledgement' => ['sometimes', 'boolean'],
        ];
    }
}
