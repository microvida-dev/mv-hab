<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeProcess;
use App\Services\Administrative\AdministrativeTimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationProcessController extends Controller
{
    public function __construct(private readonly AdministrativeTimelineService $timelineService) {}

    public function index(Request $request): View
    {
        $processes = AdministrativeProcess::query()
            ->with(['application', 'contest', 'program', 'currentCorrectionRequest'])
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->latest()
            ->paginate(10);

        return view('candidate.processes.index', compact('processes'));
    }

    public function show(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('view', $administrativeProcess);
        $administrativeProcess->load([
            'application',
            'contest',
            'program',
            'correctionRequests' => fn ($query) => $query->where('candidate_visible', true)->latest(),
            'correctionRequests.items',
            'decisions' => fn ($query) => $query->where('candidate_visible', true)->latest(),
        ]);

        return view('candidate.processes.show', ['process' => $administrativeProcess]);
    }

    public function timeline(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('view', $administrativeProcess);

        return view('candidate.processes.timeline', [
            'process' => $administrativeProcess,
            'timeline' => $this->timelineService->forCandidate($administrativeProcess),
        ]);
    }
}
