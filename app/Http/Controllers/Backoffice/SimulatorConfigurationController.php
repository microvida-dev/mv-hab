<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulator\UpdateSimulatorConfigurationRequest;
use App\Models\SimulatorConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SimulatorConfigurationController extends Controller
{
    public function edit(Request $request): View
    {
        $configuration = $this->configuration();

        Gate::authorize('view', $configuration);

        return view('backoffice.simulator.configuration.edit', compact('configuration'));
    }

    public function update(UpdateSimulatorConfigurationRequest $request): RedirectResponse
    {
        $configuration = $this->configuration();

        Gate::authorize('update', $configuration);

        $configuration->fill($request->validated());
        $configuration->forceFill([
            'is_active' => $request->boolean('is_active'),
            'anonymous_simulator_enabled' => $request->boolean('anonymous_simulator_enabled'),
            'candidate_simulator_enabled' => $request->boolean('candidate_simulator_enabled'),
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();

        return to_route('backoffice.simulator.configuration.edit')
            ->with('success', 'Configuração do simulador atualizada.');
    }

    private function configuration(): SimulatorConfiguration
    {
        return SimulatorConfiguration::query()->firstOrCreate(
            ['name' => 'Configuração geral do simulador'],
            [
                'is_active' => true,
                'anonymous_simulator_enabled' => true,
                'candidate_simulator_enabled' => true,
                'max_recommended_contests' => 5,
                'default_effort_rate' => 35,
                'session_retention_days' => 30,
            ],
        );
    }
}
