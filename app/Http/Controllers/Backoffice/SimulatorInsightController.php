<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\SimulationSession;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SimulatorInsightController extends Controller
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', SimulationSession::class);
        $actor = $this->authenticatedUser($request);
        $query = $this->municipalScope->simulationSessions(
            SimulationSession::query(),
            $actor,
        );

        $sessions = (clone $query)
            ->with(['user', 'result'])
            ->latest()
            ->paginate(20);

        $metrics = [
            'total' => (clone $query)->count(),
            'anonymous' => (clone $query)->whereNull('user_id')->count(),
            'authenticated' => (clone $query)->whereNotNull('user_id')->count(),
            'converted' => (clone $query)->whereNotNull('converted_at')->count(),
        ];

        return view('backoffice.simulator.insights.index', compact('sessions', 'metrics'));
    }

    public function show(Request $request, SimulationSession $simulationSession): View
    {
        Gate::authorize('viewBackoffice', $simulationSession);

        $simulationSession->load(['user', 'inputSnapshot', 'result.impediments', 'result.recommendedContests.contest.program']);

        return view('backoffice.simulator.insights.show', compact('simulationSession'));
    }
}
