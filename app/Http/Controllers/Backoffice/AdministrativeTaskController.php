<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdministrativeTaskRequest;
use App\Http\Requests\UpdateAdministrativeTaskRequest;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeTask;
use App\Services\Administrative\AdministrativeTaskService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdministrativeTaskController extends Controller
{
    public function __construct(
        private readonly AdministrativeTaskService $taskService,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', AdministrativeTask::class);
        $tasks = $this->municipalScope
            ->administrativeTasks(
                AdministrativeTask::query(),
                $this->authenticatedUser($request),
            )
            ->with(['administrativeProcess', 'application', 'assignedTo'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest()
            ->paginate(20);

        return view('backoffice.administrative-tasks.index', compact('tasks'));
    }

    public function store(StoreAdministrativeTaskRequest $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('assignBackoffice', $administrativeProcess);
        $this->taskService->create($administrativeProcess, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Tarefa criada.');
    }

    public function update(UpdateAdministrativeTaskRequest $request, AdministrativeTask $administrativeTask): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $administrativeTask);
        $this->taskService->update($administrativeTask, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Tarefa atualizada.');
    }

    public function complete(Request $request, AdministrativeTask $administrativeTask): RedirectResponse
    {
        Gate::authorize('completeBackoffice', $administrativeTask);
        $this->taskService->complete($administrativeTask, $this->authenticatedUser($request));

        return back()->with('success', 'Tarefa concluída.');
    }

    public function cancel(Request $request, AdministrativeTask $administrativeTask): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $administrativeTask);
        $this->taskService->cancel($administrativeTask, $this->authenticatedUser($request));

        return back()->with('success', 'Tarefa cancelada.');
    }
}
