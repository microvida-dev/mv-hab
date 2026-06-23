<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveApplicationSimulationInconsistencyRequest;
use App\Models\ApplicationSimulationInconsistency;
use App\Services\CandidateExperience\ApplicationSimulationConsistencyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ApplicationSimulationInconsistencyController extends Controller
{
    public function __construct(private readonly ApplicationSimulationConsistencyService $consistency) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ApplicationSimulationInconsistency::class);

        return view('backoffice.application-inconsistencies.index', [
            'inconsistencies' => ApplicationSimulationInconsistency::query()
                ->with(['application.contest', 'user', 'simulationSession', 'resolvedBy'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function resolve(ResolveApplicationSimulationInconsistencyRequest $request, ApplicationSimulationInconsistency $inconsistency): RedirectResponse
    {
        $this->consistency->resolve($inconsistency, $this->authenticatedUser($request), $request->validated('resolution_note'));

        return back()->with('success', 'Inconsistência marcada como resolvida.');
    }
}
