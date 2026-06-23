<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\CandidateInteraction;
use App\Services\CandidateExperience\CandidateInteractionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CandidateInteractionController extends Controller
{
    public function __construct(private readonly CandidateInteractionService $interactions) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CandidateInteraction::class);
        $user = $this->authenticatedUser($request);

        return view('candidate.interactions.index', [
            'interactions' => $this->interactions->forCandidate($user),
            'inconsistencies' => ApplicationSimulationInconsistency::query()
                ->forUser($user)
                ->open()
                ->with(['application.contest', 'simulationSession'])
                ->latest()
                ->get(),
        ]);
    }
}
