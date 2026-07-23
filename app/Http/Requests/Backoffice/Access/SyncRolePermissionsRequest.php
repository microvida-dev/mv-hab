<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && (bool) $this->user()?->can('update', $role);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'integer', 'distinct', 'exists:permissions,id'],
            'justification' => ['required', 'string', 'max:1000', 'not_regex:/<[^>]*>/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'justification' => is_string($this->input('justification')) ? trim($this->input('justification')) : $this->input('justification'),
        ]);
    }
}
