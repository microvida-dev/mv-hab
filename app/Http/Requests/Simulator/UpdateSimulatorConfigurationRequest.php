<?php

namespace App\Http\Requests\Simulator;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSimulatorConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('simulator', 'update') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'anonymous_simulator_enabled' => ['nullable', 'boolean'],
            'candidate_simulator_enabled' => ['nullable', 'boolean'],
            'max_recommended_contests' => ['required', 'integer', 'min:1', 'max:20'],
            'default_effort_rate' => ['required', 'numeric', 'min:1', 'max:100'],
            'session_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
