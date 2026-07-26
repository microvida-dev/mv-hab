<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulator\UpdateSimulatorConfigurationRequest;
use App\Models\SimulatorConfiguration;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SimulatorConfigurationController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function edit(Request $request): View
    {
        $configuration = $this->configuration($this->authenticatedUser($request));

        Gate::authorize('viewBackoffice', $configuration);

        return view('backoffice.simulator.configuration.edit', compact('configuration'));
    }

    public function update(UpdateSimulatorConfigurationRequest $request): RedirectResponse
    {
        $actor = $this->authenticatedUser($request);
        $configuration = $this->configuration($actor);

        Gate::authorize('updateBackoffice', $configuration);

        $configuration->fill($request->validated());
        $configuration->forceFill([
            'municipality_id' => $actor->municipality_id,
            'is_active' => $request->boolean('is_active'),
            'anonymous_simulator_enabled' => $request->boolean('anonymous_simulator_enabled'),
            'candidate_simulator_enabled' => $request->boolean('candidate_simulator_enabled'),
            'created_by' => $configuration->created_by ?? $actor->id,
            'updated_by' => $actor->id,
        ])->save();
        $this->auditLogger->record(
            AuditEvents::UPDATE,
            $configuration,
            'simulator',
            'update_configuration',
            'Configuração municipal do simulador atualizada.',
        );

        return to_route('backoffice.simulator.configuration.edit')
            ->with('success', 'Configuração do simulador atualizada.');
    }

    private function configuration(User $actor): SimulatorConfiguration
    {
        $configuration = $this->municipalScope
            ->simulatorConfigurations(SimulatorConfiguration::query(), $actor)
            ->where('name', 'Configuração geral do simulador')
            ->first();

        if ($configuration instanceof SimulatorConfiguration) {
            return $configuration;
        }

        $configuration = new SimulatorConfiguration([
            'name' => 'Configuração geral do simulador',
            'is_active' => true,
            'anonymous_simulator_enabled' => true,
            'candidate_simulator_enabled' => true,
            'max_recommended_contests' => 5,
            'default_effort_rate' => 35,
            'session_retention_days' => 30,
        ]);
        $configuration->forceFill(['municipality_id' => $actor->municipality_id]);

        return $configuration;
    }
}
