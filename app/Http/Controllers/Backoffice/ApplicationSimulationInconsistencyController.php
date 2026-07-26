<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveApplicationSimulationInconsistencyRequest;
use App\Models\ApplicationSimulationInconsistency;
use App\Services\CandidateExperience\ApplicationSimulationConsistencyService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationSimulationInconsistencyController extends Controller
{
    public function __construct(
        private readonly ApplicationSimulationConsistencyService $consistency,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', ApplicationSimulationInconsistency::class);

        return view('backoffice.application-inconsistencies.index', [
            'inconsistencies' => $this->municipalScope
                ->applicationSimulationInconsistencies(
                    ApplicationSimulationInconsistency::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['application.contest', 'user', 'simulationSession', 'resolvedBy'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function resolve(ResolveApplicationSimulationInconsistencyRequest $request, ApplicationSimulationInconsistency $inconsistency): RedirectResponse
    {
        Gate::authorize('decideBackoffice', $inconsistency);
        $this->consistency->resolve($inconsistency, $this->authenticatedUser($request), $request->validated('resolution_note'));

        return back()->with('success', 'Inconsistência marcada como resolvida.');
    }
}
