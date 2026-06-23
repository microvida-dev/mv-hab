<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulator\ConvertSimulationToPrefillRequest;
use App\Http\Requests\Simulator\SaveSimulationRequest;
use App\Http\Requests\Simulator\StoreCandidateSimulationRequest;
use App\Models\Application;
use App\Models\Contest;
use App\Models\SimulationSession;
use App\Services\Simulator\AdvancedEligibilitySimulatorService;
use App\Services\Simulator\ApplicationPrefillService;
use App\Services\Simulator\SimulationMessageService;
use App\Services\Simulator\SimulationSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SimulationController extends Controller
{
    public function __construct(
        private readonly AdvancedEligibilitySimulatorService $simulatorService,
        private readonly SimulationSessionService $sessionService,
        private readonly ApplicationPrefillService $prefillService,
        private readonly SimulationMessageService $messageService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SimulationSession::class);

        $sessions = SimulationSession::query()
            ->forUser($this->authenticatedUser($request))
            ->with(['result', 'recommendedContests.contest'])
            ->latest()
            ->paginate(10);

        return view('candidate.simulations.index', compact('sessions'));
    }

    public function create(): View
    {
        Gate::authorize('create', SimulationSession::class);

        $contests = Contest::query()
            ->publiclyVisible()
            ->with('program')
            ->latest('published_at')
            ->limit(20)
            ->get();
        $notices = $this->messageService->notices();

        return view('candidate.simulations.create', compact('contests', 'notices'));
    }

    public function store(StoreCandidateSimulationRequest $request): RedirectResponse
    {
        Gate::authorize('create', SimulationSession::class);

        $session = $this->simulatorService->simulateForUser(
            $this->authenticatedUser($request),
            $request->validated(),
            $request,
        );

        return to_route('candidate.simulations.show', $session)
            ->with('success', 'Simulação concluída. Reveja o resultado indicativo antes de avançar.');
    }

    public function show(SimulationSession $simulationSession): View
    {
        Gate::authorize('view', $simulationSession);

        $simulationSession->load(['inputSnapshot', 'result.impediments', 'result.recommendedContests.contest.program']);
        $notices = $this->messageService->notices();

        return view('candidate.simulations.show', compact('simulationSession', 'notices'));
    }

    public function save(SaveSimulationRequest $request, SimulationSession $simulationSession): RedirectResponse
    {
        Gate::authorize('update', $simulationSession);

        $this->sessionService->markSaved($simulationSession);

        return to_route('candidate.simulations.show', $simulationSession)
            ->with('success', 'Simulação guardada no histórico da área pessoal.');
    }

    public function convertToPrefill(ConvertSimulationToPrefillRequest $request, SimulationSession $simulationSession): RedirectResponse
    {
        Gate::authorize('update', $simulationSession);

        $application = null;
        if ($request->integer('application_id') > 0) {
            $application = Application::query()
                ->where('user_id', $this->authenticatedUser($request)->id)
                ->findOrFail($request->integer('application_id'));
        }

        $prefill = $this->prefillService->createFromSimulation(
            $this->authenticatedUser($request),
            $simulationSession,
            $application,
        );

        return to_route('candidate.application-prefills.show', $prefill)
            ->with('success', 'Pré-preenchimento criado. Confirme os dados antes de aplicar ao rascunho.');
    }
}
