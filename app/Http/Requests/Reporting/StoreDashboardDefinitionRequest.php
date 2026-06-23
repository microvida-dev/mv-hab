<?php

namespace App\Http\Requests\Reporting;

use App\Enums\DashboardType;
use App\Models\DashboardDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDashboardDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DashboardDefinition::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'alpha_dash', 'max:150', Rule::unique('dashboard_definitions', 'code')->ignore($this->route('dashboardDefinition'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'dashboard_type' => ['required', Rule::enum(DashboardType::class)],
            'required_permission' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
