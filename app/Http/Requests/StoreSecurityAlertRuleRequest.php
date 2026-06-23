<?php

namespace App\Http\Requests;

use App\Enums\SecurityAlertSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSecurityAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('settings.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_code' => ['required', 'string', 'max:150'],
            'severity' => ['required', Rule::in(SecurityAlertSeverity::values())],
            'threshold' => ['nullable', 'integer', 'min:1'],
            'window_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
