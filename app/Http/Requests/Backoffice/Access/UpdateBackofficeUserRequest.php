<?php

namespace App\Http\Requests\Backoffice\Access;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBackofficeUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('users.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mfa_required' => ['nullable', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
