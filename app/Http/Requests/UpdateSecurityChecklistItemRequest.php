<?php

namespace App\Http\Requests;

use App\Enums\SecurityChecklistStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSecurityChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('settings.audit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SecurityChecklistStatus::values())],
            'evidence' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
