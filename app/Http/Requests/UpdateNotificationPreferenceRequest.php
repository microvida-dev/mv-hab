<?php

namespace App\Http\Requests;

use App\Enums\CommunicationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['allow_in_app', 'allow_email', 'allow_sms', 'allow_postal'] as $field) {
            $this->merge([$field => $this->boolean($field)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'allow_in_app' => ['boolean'],
            'allow_email' => ['boolean'],
            'allow_sms' => ['boolean'],
            'allow_postal' => ['boolean'],
            'preferred_channel' => ['nullable', Rule::in(CommunicationChannel::values())],
            'email_for_notifications' => ['nullable', 'email', 'max:255'],
            'phone_for_notifications' => ['nullable', 'string', 'max:50'],
            'postal_address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
