<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdministrativeWorkflowConfigRequest;
use App\Http\Requests\UpdateAdministrativeWorkflowConfigRequest;
use App\Models\AdministrativeWorkflowConfig;
use App\Models\Contest;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdministrativeWorkflowConfigController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', AdministrativeWorkflowConfig::class);

        return view('backoffice.administrative-workflow-configs.index', [
            'configs' => $this->municipalScope
                ->administrativeWorkflowConfigs(
                    AdministrativeWorkflowConfig::query(),
                    $this->currentUser(),
                )
                ->with(['program', 'contest'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', AdministrativeWorkflowConfig::class);

        return view('backoffice.administrative-workflow-configs.create', $this->formData());
    }

    public function store(StoreAdministrativeWorkflowConfigRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', AdministrativeWorkflowConfig::class);
        $this->authorizeContext($request);
        $config = AdministrativeWorkflowConfig::query()->create(
            $this->normalized($request->validated()),
        );
        $this->audit->record(
            AuditEvents::CREATE,
            $config,
            'settings',
            'administrative_workflow_config_created',
            'Configuração de workflow administrativo criada.',
        );

        return to_route('backoffice.administrative-workflow-configs.index')->with('success', 'Configuração criada.');
    }

    public function edit(AdministrativeWorkflowConfig $administrativeWorkflowConfig): View
    {
        Gate::authorize('updateBackoffice', $administrativeWorkflowConfig);

        return view('backoffice.administrative-workflow-configs.edit', [
            'config' => $administrativeWorkflowConfig,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateAdministrativeWorkflowConfigRequest $request, AdministrativeWorkflowConfig $administrativeWorkflowConfig): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $administrativeWorkflowConfig);
        $this->authorizeContext($request);
        $administrativeWorkflowConfig->update($this->normalized($request->validated()));
        $this->audit->record(
            AuditEvents::UPDATE,
            $administrativeWorkflowConfig,
            'settings',
            'administrative_workflow_config_updated',
            'Configuração de workflow administrativo atualizada.',
        );

        return to_route('backoffice.administrative-workflow-configs.index')->with('success', 'Configuração atualizada.');
    }

    public function activate(Request $request, AdministrativeWorkflowConfig $administrativeWorkflowConfig): RedirectResponse
    {
        Gate::authorize('activateBackoffice', $administrativeWorkflowConfig);
        $administrativeWorkflowConfig->forceFill(['is_active' => true])->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $administrativeWorkflowConfig,
            'settings',
            'administrative_workflow_config_activated',
            'Configuração de workflow administrativo ativada.',
        );

        return back()->with('success', 'Configuração ativada.');
    }

    public function deactivate(Request $request, AdministrativeWorkflowConfig $administrativeWorkflowConfig): RedirectResponse
    {
        Gate::authorize('deactivateBackoffice', $administrativeWorkflowConfig);
        $administrativeWorkflowConfig->forceFill(['is_active' => false])->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $administrativeWorkflowConfig,
            'settings',
            'administrative_workflow_config_deactivated',
            'Configuração de workflow administrativo desativada.',
        );

        return back()->with('success', 'Configuração desativada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => $this->municipalScope
                ->programs(Program::query(), $this->currentUser())
                ->orderBy('name')
                ->get(['id', 'name']),
            'contests' => $this->municipalScope
                ->contests(Contest::query(), $this->currentUser())
                ->orderBy('title')
                ->get(['id', 'title']),
        ];
    }

    private function authorizeContext(Request $request): void
    {
        $user = $this->authenticatedUser($request);
        $programId = $request->integer('program_id');
        $contestId = $request->integer('contest_id');

        if ($programId > 0) {
            abort_unless(
                $this->municipalScope
                    ->programs(Program::query(), $user)
                    ->whereKey($programId)
                    ->exists(),
                403,
            );
        }

        if ($contestId > 0) {
            $contest = $this->municipalScope
                ->contests(Contest::query(), $user)
                ->whereKey($contestId)
                ->firstOrFail();
            abort_if(
                $programId > 0
                    && (int) $contest->program_id !== $programId,
                422,
            );
        }
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
