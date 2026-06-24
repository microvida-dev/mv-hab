<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReassignWorkTaskRequest;
use App\Http\Requests\UpdateWorkTaskStatusRequest;
use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskAssignmentService;
use App\Services\Workflows\WorkTaskDashboardService;
use App\Services\Workflows\WorkTaskStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkTaskController extends Controller
{
    public function __construct(
        private readonly WorkTaskAssignmentService $assignmentService,
        private readonly WorkTaskStatusService $statusService,
        private readonly WorkTaskDashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', WorkTask::class);

        $tasks = $this->filteredTasks($request)->latest()->paginate(20)->withQueryString();

        return view('backoffice.work-tasks.index', [
            'tasks' => $tasks,
            'scope' => 'all',
            'filters' => $request->only(['status', 'priority', 'type', 'due']),
        ]);
    }

    public function my(Request $request): View
    {
        Gate::authorize('viewAny', WorkTask::class);

        $tasks = $this->filteredTasks($request)
            ->where('assigned_user_id', $this->authenticatedUser($request)->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.work-tasks.index', [
            'tasks' => $tasks,
            'scope' => 'my',
            'filters' => $request->only(['status', 'priority', 'type', 'due']),
        ]);
    }

    public function team(Request $request): View
    {
        Gate::authorize('viewAny', WorkTask::class);

        $teamIds = $this->authenticatedUser($request)->municipalTeams()
            ->wherePivotNull('left_at')
            ->pluck('municipal_teams.id');

        $tasks = $this->filteredTasks($request)
            ->whereIn('municipal_team_id', $teamIds)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.work-tasks.index', [
            'tasks' => $tasks,
            'scope' => 'team',
            'filters' => $request->only(['status', 'priority', 'type', 'due']),
        ]);
    }

    public function overdue(Request $request): View
    {
        Gate::authorize('viewAny', WorkTask::class);

        $tasks = $this->filteredTasks($request)
            ->where('status', WorkTask::STATUS_OVERDUE)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.work-tasks.index', [
            'tasks' => $tasks,
            'scope' => 'overdue',
            'filters' => $request->only(['status', 'priority', 'type', 'due']),
        ]);
    }

    public function show(WorkTask $workTask): View
    {
        Gate::authorize('view', $workTask);

        $workTask->load(['municipalTeam', 'assignedUser', 'histories.actor']);

        return view('backoffice.work-tasks.show', [
            'task' => $workTask,
            'teams' => MunicipalTeam::query()->where('status', 'active')->orderBy('name')->get(),
            'users' => User::query()
                ->whereNull('deactivated_at')
                ->where(function ($query): void {
                    $query->whereNull('status')->orWhere('status', 'active');
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function claim(Request $request, WorkTask $workTask): RedirectResponse
    {
        Gate::authorize('claim', $workTask);
        $this->assignmentService->claim($workTask, $this->authenticatedUser($request));

        return back()->with('success', 'Tarefa assumida.');
    }

    public function reassign(ReassignWorkTaskRequest $request, WorkTask $workTask): RedirectResponse
    {
        Gate::authorize('reassign', $workTask);

        $validated = $request->validated();
        $team = isset($validated['municipal_team_id'])
            ? MunicipalTeam::query()->findOrFail((int) $validated['municipal_team_id'])
            : null;
        $assignee = isset($validated['assigned_user_id'])
            ? User::query()->findOrFail((int) $validated['assigned_user_id'])
            : null;

        $this->assignmentService->reassign(
            task: $workTask,
            actor: $this->authenticatedUser($request),
            team: $team,
            assignee: $assignee,
            reason: (string) $validated['reason'],
        );

        return back()->with('success', 'Tarefa reatribuída.');
    }

    public function updateStatus(UpdateWorkTaskStatusRequest $request, WorkTask $workTask): RedirectResponse
    {
        $validated = $request->validated();
        $status = (string) $validated['status'];
        $actor = $this->authenticatedUser($request);

        if ($status === WorkTask::STATUS_COMPLETED) {
            Gate::authorize('complete', $workTask);
            $this->statusService->complete($workTask, $actor, (string) $validated['outcome_note']);

            return back()->with('success', 'Tarefa concluída.');
        }

        if ($status === WorkTask::STATUS_CANCELLED) {
            Gate::authorize('cancel', $workTask);
            $this->statusService->cancel($workTask, $actor, (string) $validated['cancellation_reason']);

            return back()->with('success', 'Tarefa cancelada.');
        }

        Gate::authorize('updateStatus', $workTask);

        if ($status === WorkTask::STATUS_IN_ANALYSIS) {
            $this->statusService->start($workTask, $actor, $validated['note'] ?? null);
        } else {
            $this->statusService->wait($workTask, $actor, $status, (string) ($validated['note'] ?? 'Tarefa colocada em espera.'));
        }

        return back()->with('success', 'Estado da tarefa atualizado.');
    }

    /** @return Builder<WorkTask> */
    private function filteredTasks(Request $request): Builder
    {
        return $this->dashboardService->visibleQuery($this->authenticatedUser($request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->query('due') === 'soon', fn ($query) => $query->whereBetween('due_at', [now(), now()->addDays(2)]))
            ->when($request->query('due') === 'overdue', fn ($query) => $query->where('status', WorkTask::STATUS_OVERDUE));
    }
}
