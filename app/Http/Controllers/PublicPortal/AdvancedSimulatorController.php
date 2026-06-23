<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulator\StoreAnonymousSimulationRequest;
use App\Models\Contest;
use App\Models\SimulationSession;
use App\Services\Simulator\AdvancedEligibilitySimulatorService;
use App\Services\Simulator\SimulationMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdvancedSimulatorController extends Controller
{
    public function __construct(
        private readonly AdvancedEligibilitySimulatorService $simulatorService,
        private readonly SimulationMessageService $messageService,
    ) {}

    public function show(): View
    {
        $contests = Contest::query()
            ->publiclyVisible()
            ->with('program')
            ->latest('published_at')
            ->limit(20)
            ->get();
        $notices = $this->messageService->notices();

        return view('public.simulator.show', compact('contests', 'notices'));
    }

    public function simulate(StoreAnonymousSimulationRequest $request): RedirectResponse
    {
        $session = $this->simulatorService->simulateAnonymous($request->validated(), $request);

        return to_route('public.simulator.result', ['uuid' => $session->uuid]);
    }

    public function result(Request $request, string $uuid): View
    {
        $session = SimulationSession::query()
            ->where('uuid', $uuid)
            ->whereNull('user_id')
            ->with(['inputSnapshot', 'result.impediments', 'result.recommendedContests.contest.program'])
            ->firstOrFail();
        $notices = $this->messageService->notices();

        return view('public.simulator.result', compact('session', 'notices'));
    }
}
