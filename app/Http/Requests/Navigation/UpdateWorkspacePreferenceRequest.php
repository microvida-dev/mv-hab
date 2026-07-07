<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspacePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferred_workspace' => ['nullable', 'string', 'max:100'],
            'collapsed_groups' => ['nullable', 'array'],
            'collapsed_groups.*' => ['string', 'max:150'],
            'hidden_modules' => ['nullable', 'array'],
            'hidden_modules.*' => ['string', 'max:150'],
            'dashboard_layout' => ['nullable', 'array'],
            'workspace_layout' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
