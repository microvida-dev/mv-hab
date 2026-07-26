<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\HearingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\IssueHearingRequest;
use App\Http\Requests\StoreHearingRequest;
use App\Models\Application;
use App\Models\Hearing;
use App\Services\Hearings\HearingService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HearingController extends Controller
{
    public function __construct(
        private readonly HearingService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', Hearing::class);
        $hearings = $this->municipalScope
            ->hearings(Hearing::query(), $this->authenticatedUser($request))
            ->with(['candidate', 'application', 'provisionalList', 'definitiveList'])
            ->latest()
            ->paginate(20);

        return view('backoffice.hearings.index', compact('hearings'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', Hearing::class);
        $applications = $this->municipalScope
            ->applications(Application::query(), $this->authenticatedUser($request))
            ->with('user')
            ->latest()
            ->limit(100)
            ->get();

        return view('backoffice.hearings.create', ['applications' => $applications, 'types' => HearingType::options()]);
    }

    public function store(StoreHearingRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', Hearing::class);
        $hearing = $this->service->create($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.hearings.show', $hearing)->with('success', 'Audiência criada.');
    }

    public function show(Hearing $hearing): View
    {
        Gate::authorize('viewBackoffice', $hearing);
        $hearing->load(['candidate', 'application', 'provisionalList', 'definitiveList', 'submissions.documentSubmission']);

        return view('backoffice.hearings.show', compact('hearing'));
    }

    public function issue(IssueHearingRequest $request, Hearing $hearing): RedirectResponse
    {
        Gate::authorize('issueBackoffice', $hearing);
        $this->service->issue($hearing, $this->authenticatedUser($request));

        return back()->with('success', 'Audiência emitida ao candidato.');
    }

    public function close(Request $request, Hearing $hearing): RedirectResponse
    {
        Gate::authorize('closeBackoffice', $hearing);
        $this->service->close($hearing, $this->authenticatedUser($request));

        return back()->with('success', 'Audiência fechada.');
    }

    public function cancel(Request $request, Hearing $hearing): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $hearing);
        $this->service->cancel($hearing, $this->authenticatedUser($request));

        return back()->with('success', 'Audiência cancelada.');
    }
}
