<?php

namespace App\Http\Requests\Simulator;

use App\Models\SimulatorConfiguration;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSimulatorConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || $user->municipality_id === null) {
            return false;
        }

        $configuration = app(MunicipalRecordScopeService::class)
            ->simulatorConfigurations(SimulatorConfiguration::query(), $user)
            ->where('name', 'Configuração geral do simulador')
            ->first() ?? new SimulatorConfiguration([
                'name' => 'Configuração geral do simulador',
            ]);
        $configuration->forceFill(['municipality_id' => $user->municipality_id]);

        return $user->can('updateBackoffice', $configuration);
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
