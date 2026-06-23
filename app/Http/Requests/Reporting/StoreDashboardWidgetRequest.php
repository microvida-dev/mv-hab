<?php

namespace App\Http\Requests\Reporting;

use App\Enums\DashboardWidgetType;
use App\Models\DashboardWidget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDashboardWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DashboardWidget::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dashboard_definition_id' => ['required', 'integer', 'exists:dashboard_definitions,id'],
            'indicator_definition_id' => ['nullable', 'integer', 'exists:indicator_definitions,id'],
            'code' => ['required', 'alpha_dash', 'max:150'],
            'title' => ['required', 'string', 'max:255'],
            'widget_type' => ['required', Rule::enum(DashboardWidgetType::class)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'width' => ['nullable', 'integer', 'min:1', 'max:4'],
            'required_permission' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
