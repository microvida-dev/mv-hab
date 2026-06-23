<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveListAutomationRunRequest;
use App\Http\Requests\RunDefinitiveListAutomationRequest;
use App\Http\Requests\RunProvisionalListAutomationRequest;
use App\Models\Contest;
use App\Models\ListAutomationRun;
use App\Services\ListAutomation\DefinitiveListAutomationService;
use App\Services\ListAutomation\ListAutomationRunService;
use App\Services\ListAutomation\ProvisionalListAutomationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ListAutomationController extends Controller
{
    public function __construct(
        private readonly ProvisionalListAutomationService $provisional,
        private readonly DefinitiveListAutomationService $definitive,
        private readonly ListAutomationRunService $runs,
    ) {}

    public function index(Contest $contest): View
    {
        Gate::authorize('viewAny', ListAutomationRun::class);
        $runs = $contest->listAutomationRuns()->latest()->paginate(20);

        return view('backoffice.list-automation.index', compact('contest', 'runs'));
    }

    public function show(ListAutomationRun $listAutomationRun): View
    {
        Gate::authorize('view', $listAutomationRun);

        return view('backoffice.list-automation.show', compact('listAutomationRun'));
    }

    public function generateProvisional(RunProvisionalListAutomationRequest $request, Contest $contest): RedirectResponse
    {
        Gate::authorize('create', ListAutomationRun::class);
        $run = $this->provisional->run($contest, $this->authenticatedUser($request));

        return to_route('backoffice.lists.automation-runs.show', $run)->with('success', 'Lista provisória gerada para revisão.');
    }

    public function generateDefinitive(RunDefinitiveListAutomationRequest $request, Contest $contest): RedirectResponse
    {
        Gate::authorize('create', ListAutomationRun::class);
        $run = $this->definitive->run($contest, $this->authenticatedUser($request));

        return to_route('backoffice.lists.automation-runs.show', $run)->with('success', 'Lista definitiva gerada para revisão.');
    }

    public function approve(ApproveListAutomationRunRequest $request, ListAutomationRun $listAutomationRun): RedirectResponse
    {
        Gate::authorize('approve', $listAutomationRun);
        $this->runs->approve($listAutomationRun, $this->authenticatedUser($request));

        return back()->with('success', 'Automação aprovada.');
    }
}
