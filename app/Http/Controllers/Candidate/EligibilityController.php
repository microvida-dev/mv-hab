<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunCandidatePreCheckRequest;
use App\Models\Contest;
use App\Models\EligibilityCheck;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use App\Services\Eligibility\EligibilityCheckService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EligibilityController extends Controller
{
    public function __construct(private readonly EligibilityCheckService $checkService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EligibilityCheck::class);

        $ruleSets = EligibilityRuleSet::query()
            ->active()
            ->with(['program', 'contest'])
            ->whereHas('criteria', fn ($query) => $query->where('is_active', true))
            ->orderByDesc('contest_id')
            ->orderBy('name')
            ->get();
        $latestCheck = $this->checkService->latestFor($this->authenticatedUser($request));

        return view('candidate.eligibility.index', compact('ruleSets', 'latestCheck'));
    }

    public function preCheck(RunCandidatePreCheckRequest $request): RedirectResponse
    {
        $program = $request->filled('program_id')
            ? Program::query()->findOrFail($request->integer('program_id'))
            : null;
        $contest = $request->filled('contest_id')
            ? Contest::query()->with('program')->findOrFail($request->integer('contest_id'))
            : null;
        $check = $this->checkService->candidatePreCheck($this->authenticatedUser($request), $program, $contest);

        return to_route('candidate.eligibility.show', $check)
            ->with('success', 'Pré-verificação concluída.');
    }

    public function show(EligibilityCheck $eligibilityCheck): View
    {
        Gate::authorize('view', $eligibilityCheck);
        $eligibilityCheck->load(['program', 'contest', 'results']);

        return view('candidate.eligibility.show', ['check' => $eligibilityCheck]);
    }

    public function history(Request $request): View
    {
        Gate::authorize('viewAny', EligibilityCheck::class);
        $checks = $this->checkService->paginatedHistoryFor($this->authenticatedUser($request));

        return view('candidate.eligibility.history', compact('checks'));
    }
}
