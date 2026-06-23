<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Services\ProcessTracking\ApplicationPublicStatusService;
use App\Services\ProcessTracking\ProcessActionResolver;
use App\Services\ProcessTracking\ProcessTimelineBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProcessDashboardController extends Controller
{
    public function __construct(
        private readonly ApplicationPublicStatusService $statusService,
        private readonly ProcessActionResolver $actions,
        private readonly ProcessTimelineBuilder $timeline,
    ) {}

    public function index(Request $request): View
    {
        $processes = AdministrativeProcess::query()
            ->with(['application.publicStatusSnapshot', 'contest', 'program', 'currentCorrectionRequest'])
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->latest()
            ->paginate(10);

        $processes->getCollection()->each(function (AdministrativeProcess $process): void {
            $application = $process->application;

            if ($application instanceof Application) {
                $this->statusService->refresh($application);
            }
        });

        return view('candidate.processes.index', compact('processes'));
    }

    public function show(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('view', $administrativeProcess);
        $administrativeProcess->load([
            'application.publicStatusSnapshot',
            'application.officialNotifications',
            'application.additionalDocumentRequests',
            'application.additionalDocumentSubmissions',
            'application.controlledWithdrawals',
            'application.housingVisits',
            'application.supportTickets',
            'contest',
            'program',
            'correctionRequests' => fn ($query) => $query->where('candidate_visible', true)->latest(),
            'correctionRequests.items',
            'decisions' => fn ($query) => $query->where('candidate_visible', true)->latest(),
        ]);

        $application = $administrativeProcess->application;
        $hasApplication = $application instanceof Application;

        return view('candidate.processes.show', [
            'process' => $administrativeProcess,
            'publicStatus' => $hasApplication ? $this->statusService->refresh($application) : null,
            'actions' => $hasApplication ? $this->actions->forApplication($application) : collect(),
            'timeline' => $this->timeline->forCandidate($administrativeProcess)->take(6),
        ]);
    }
}
