<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\AdministrativeProcessStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignAdministrativeProcessRequest;
use App\Models\AdministrativeProcess;
use App\Models\Contest;
use App\Models\Program;
use App\Models\User;
use App\Services\Administrative\AdministrativeProcessService;
use App\Services\Administrative\AdministrativeScoringReadinessService;
use App\Services\Administrative\AdministrativeTimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdministrativeProcessController extends Controller
{
    public function __construct(
        private readonly AdministrativeProcessService $processService,
        private readonly AdministrativeTimelineService $timelineService,
        private readonly AdministrativeScoringReadinessService $scoringReadinessService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AdministrativeProcess::class);

        $processes = AdministrativeProcess::query()
            ->with(['application', 'candidate', 'contest', 'program', 'assignedTo', 'currentCorrectionRequest'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('contest_id'), fn ($query) => $query->where('contest_id', $request->integer('contest_id')))
            ->when($request->filled('program_id'), fn ($query) => $query->where('program_id', $request->integer('program_id')))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->boolean('open_corrections'), fn ($query) => $query->whereHas('correctionRequests', fn ($builder) => $builder->whereIn('status', ['issued', 'open', 'partially_responded', 'responded', 'under_review'])))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->query('search').'%';
                $query->where(function ($builder) use ($search) {
                    $builder->where('process_number', 'like', $search)
                        ->orWhereHas('application', fn ($inner) => $inner->where('application_number', 'like', $search));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.administrative-processes.index', [
            'processes' => $processes,
            'statuses' => AdministrativeProcessStatus::options(),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('view', $administrativeProcess);

        $administrativeProcess->load([
            'application.adhesionRegistration',
            'application.household.members.incomeRecords.incomeSource',
            'application.household.incomeRecords.incomeSource',
            'application.currentHousingSituation',
            'application.applicationDocuments.documentSubmission.currentVersion',
            'application.applicationDocuments.documentType',
            'application.latestEligibilityCheck',
            'candidate',
            'contest',
            'program',
            'assignedTo',
            'reviews.items',
            'correctionRequests.items',
            'correctionRequests.responses.documentSubmission',
            'decisions.decidedBy',
            'tasks.assignedTo',
            'notes.user',
        ]);

        return view('backoffice.administrative-processes.show', [
            'process' => $administrativeProcess,
            'scoringReadiness' => $this->scoringReadinessService->forProcess($administrativeProcess),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function assign(AssignAdministrativeProcessRequest $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('update', $administrativeProcess);
        $assignee = User::query()->findOrFail($request->integer('assigned_to'));
        $this->processService->assign($administrativeProcess, $assignee, $this->authenticatedUser($request));

        return to_route('backoffice.administrative-processes.show', $administrativeProcess)
            ->with('success', 'Processo atribuído com sucesso.');
    }

    public function startPreliminaryReview(Request $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('update', $administrativeProcess);
        $this->processService->startPreliminaryReview($administrativeProcess, $this->authenticatedUser($request));

        return back()->with('success', 'Triagem inicial iniciada.');
    }

    public function startDocumentReview(Request $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('update', $administrativeProcess);
        $this->processService->startDocumentReview($administrativeProcess, $this->authenticatedUser($request));

        return back()->with('success', 'Análise documental iniciada.');
    }

    public function startEligibilityReview(Request $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('update', $administrativeProcess);
        $this->processService->startEligibilityReview($administrativeProcess, $this->authenticatedUser($request));

        return back()->with('success', 'Análise de requisitos iniciada.');
    }

    public function timeline(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('audit', $administrativeProcess);

        return view('backoffice.administrative-processes.timeline', [
            'process' => $administrativeProcess,
            'timeline' => $this->timelineService->forBackoffice($administrativeProcess),
        ]);
    }
}
