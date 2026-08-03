<?php

namespace App\Http\Requests\Backoffice\Access;

use App\Models\Role;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Role::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $template = $this->filled('template_key');

        return [
            'label' => [
                Rule::excludeIf($template),
                Rule::requiredIf(! $template),
                'string',
                'max:120',
                'not_regex:/<[^>]*>/',
                Rule::unique('roles', 'label'),
            ],
            'description' => [
                Rule::excludeIf($template),
                'nullable',
                'string',
                'max:2000',
                'not_regex:/<[^>]*>/',
            ],
            'permissions' => [Rule::excludeIf($template), Rule::requiredIf(! $template), 'array', 'min:1'],
            'permissions.*' => [Rule::excludeIf($template), 'required', 'integer', 'distinct', 'exists:permissions,id'],
            'template_key' => [
                'nullable',
                'string',
                Rule::in(app(MunicipalRoleTemplateRegistry::class)->keys()),
            ],
            'confirm_template' => [Rule::excludeIf(! $template), 'required', 'accepted'],
            'confirm_reconcile' => ['sometimes', 'accepted'],
            'justification' => ['required', 'string', 'max:1000', 'not_regex:/<[^>]*>/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => is_string($this->input('label')) ? trim($this->input('label')) : $this->input('label'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
            'template_key' => is_string($this->input('template_key')) ? trim($this->input('template_key')) : $this->input('template_key'),
            'justification' => is_string($this->input('justification')) ? trim($this->input('justification')) : $this->input('justification'),
        ]);
    }
}
