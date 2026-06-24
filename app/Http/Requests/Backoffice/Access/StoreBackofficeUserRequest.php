<?php

namespace App\Http\Requests\Backoffice\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBackofficeUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('users.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'team_id' => ['nullable', 'integer', 'exists:municipal_teams,id'],
            'role_in_team' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'mfa_required' => ['nullable', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'justification' => ['required', 'string', 'max:1000'],
        ];
    }
}
