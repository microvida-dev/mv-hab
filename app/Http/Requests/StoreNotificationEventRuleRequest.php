<?php

namespace App\Http\Requests;

use App\Enums\CommunicationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationEventRuleRequest extends FormRequest
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
            'event_code' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'recipient_type' => ['required', Rule::in(['candidate', 'tenant', 'municipal_technician', 'jury_member', 'finance_manager', 'maintenance_manager', 'admin', 'custom_user'])],
            'channel' => ['required', Rule::in(CommunicationChannel::values())],
            'notification_template_id' => ['required', 'exists:notification_templates,id'],
            'requires_acknowledgement' => ['sometimes', 'boolean'],
            'priority' => ['required', Rule::in(NotificationPriority::values())],
            'send_immediately' => ['sometimes', 'boolean'],
            'delay_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
