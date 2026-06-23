<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\SimulationSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SimulatorInsightController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewInsights', SimulationSession::class);

        $sessions = SimulationSession::query()
            ->with(['user', 'result'])
            ->latest()
            ->paginate(20);

        $metrics = [
            'total' => SimulationSession::query()->count(),
            'anonymous' => SimulationSession::query()->whereNull('user_id')->count(),
            'authenticated' => SimulationSession::query()->whereNotNull('user_id')->count(),
            'converted' => SimulationSession::query()->whereNotNull('converted_at')->count(),
        ];

        return view('backoffice.simulator.insights.index', compact('sessions', 'metrics'));
    }

    public function show(SimulationSession $simulationSession): View
    {
        Gate::authorize('viewInsights', SimulationSession::class);

        $simulationSession->load(['user', 'inputSnapshot', 'result.impediments', 'result.recommendedContests.contest.program']);

        return view('backoffice.simulator.insights.show', compact('simulationSession'));
    }
}
