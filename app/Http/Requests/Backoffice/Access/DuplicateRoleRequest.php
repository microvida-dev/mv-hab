<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && (bool) $this->user()?->can('duplicate', $role);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                'max:120',
                'not_regex:/<[^>]*>/',
                Rule::unique('roles', 'label'),
            ],
            'description' => ['nullable', 'string', 'max:2000', 'not_regex:/<[^>]*>/'],
            'justification' => ['required', 'string', 'max:1000', 'not_regex:/<[^>]*>/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => is_string($this->input('label')) ? trim($this->input('label')) : $this->input('label'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
            'justification' => is_string($this->input('justification')) ? trim($this->input('justification')) : $this->input('justification'),
        ]);
    }
}
