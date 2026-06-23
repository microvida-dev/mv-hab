<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdministrativeWorkflowConfigRequest;
use App\Http\Requests\UpdateAdministrativeWorkflowConfigRequest;
use App\Models\AdministrativeWorkflowConfig;
use App\Models\Contest;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdministrativeWorkflowConfigController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AdministrativeWorkflowConfig::class);

        return view('backoffice.administrative-workflow-configs.index', [
            'configs' => AdministrativeWorkflowConfig::query()->with(['program', 'contest'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', AdministrativeWorkflowConfig::class);

        return view('backoffice.administrative-workflow-configs.create', $this->formData());
    }

    public function store(StoreAdministrativeWorkflowConfigRequest $request): RedirectResponse
    {
        Gate::authorize('create', AdministrativeWorkflowConfig::class);
        AdministrativeWorkflowConfig::query()->create($this->normalized($request->validated()));

        return to_route('backoffice.administrative-workflow-configs.index')->with('success', 'Configuração criada.');
    }

    public function edit(AdministrativeWorkflowConfig $administrativeWorkflowConfig): View
    {
        Gate::authorize('update', $administrativeWorkflowConfig);

        return view('backoffice.administrative-workflow-configs.edit', [
            'config' => $administrativeWorkflowConfig,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateAdministrativeWorkflowConfigRequest $request, AdministrativeWorkflowConfig $administrativeWorkflowConfig): RedirectResponse
    {
        Gate::authorize('update', $administrativeWorkflowConfig);
        $administrativeWorkflowConfig->update($this->normalized($request->validated()));

        return to_route('backoffice.administrative-workflow-configs.index')->with('success', 'Configuração atualizada.');
    }

    public function activate(Request $request, AdministrativeWorkflowConfig $administrativeWorkflowConfig): RedirectResponse
    {
        Gate::authorize('update', $administrativeWorkflowConfig);
        $administrativeWorkflowConfig->forceFill(['is_active' => true])->save();

        return back()->with('success', 'Configuração ativada.');
    }

    public function deactivate(Request $request, AdministrativeWorkflowConfig $administrativeWorkflowConfig): RedirectResponse
    {
        Gate::authorize('update', $administrativeWorkflowConfig);
        $administrativeWorkflowConfig->forceFill(['is_active' => false])->save();

        return back()->with('success', 'Configuração desativada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        foreach (['is_active', 'allow_deadline_extension', 'auto_mark_overdue', 'requires_decision_approval'] as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }

        $data['max_deadline_extensions'] = $data['max_deadline_extensions'] ?? 0;

        return $data;
    }
}
