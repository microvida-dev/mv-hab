<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Http\Requests\WithdrawApplicationRequest;
use App\Models\Application;
use App\Models\Contest;
use App\Services\Applications\ApplicationService;
use App\Services\Applications\ApplicationValidationService;
use App\Services\CandidateExperience\ApplicationSimulationConsistencyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly ApplicationValidationService $validationService,
        private readonly ApplicationSimulationConsistencyService $simulationConsistency,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Application::class);

        $applications = Application::query()
            ->forUser($this->authenticatedUser($request))
            ->with(['contest', 'program'])
            ->latest()
            ->paginate(10);

        return view('candidate.applications.index', compact('applications'));
    }

    public function create(Request $request, Contest $contest): View
    {
        Gate::authorize('create', Application::class);
        abort_unless($contest->isOpenForApplications(), 404);

        $contest->load('program.municipality');
        $readiness = $this->validationService->readinessForStart($this->authenticatedUser($request), $contest);

        return view('candidate.applications.create', compact('contest', 'readiness'));
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $contest = Contest::query()->findOrFail($request->integer('contest_id'));
        $application = $this->applicationService->createDraft(
            $this->authenticatedUser($request),
            $contest,
            $request->validated(),
        );
        $this->simulationConsistency->analyse($application->refresh());

        return to_route('candidate.applications.review', $application)
            ->with('success', 'Candidatura criada em rascunho. Reveja os dados antes de submeter.');
    }

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);

        $application->load([
            'contest',
            'program',
            'adhesionRegistration',
            'household.members',
            'household.incomeRecords',
            'currentHousingSituation',
            'applicationDocuments.documentType',
            'declarations',
            'statusHistories.changedBy',
            'latestEligibilityCheck',
            'simulationInconsistencies',
        ]);

        return view('candidate.applications.show', compact('application'));
    }

    public function edit(Application $application): View
    {
        Gate::authorize('update', $application);
        $application->load(['contest', 'program']);

        return view('candidate.applications.edit', compact('application'));
    }

    public function update(UpdateApplicationRequest $request, Application $application): RedirectResponse
    {
        $this->applicationService->updateDraft($application, $request->validated(), $this->authenticatedUser($request));
        $this->simulationConsistency->analyse($application->refresh());

        return to_route('candidate.applications.show', $application)
            ->with('success', 'Rascunho atualizado.');
    }

    public function withdraw(WithdrawApplicationRequest $request, Application $application): RedirectResponse
    {
        $this->applicationService->withdraw(
            $application,
            $this->authenticatedUser($request),
            $request->validated('reason'),
        );

        return to_route('candidate.applications.show', $application)
            ->with('success', 'A desistência da candidatura foi registada.');
    }
}
