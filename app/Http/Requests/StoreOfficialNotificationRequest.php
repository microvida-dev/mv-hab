<?php

namespace App\Http\Requests;

use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficialNotificationRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'application_id' => ['nullable', 'exists:applications,id'],
            'notification_type' => ['required', 'string', Rule::in(OfficialNotificationType::values())],
            'channel' => ['nullable', 'string', Rule::in(OfficialNotificationChannel::values())],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
