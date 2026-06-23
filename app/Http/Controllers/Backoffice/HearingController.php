<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\HearingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\IssueHearingRequest;
use App\Http\Requests\StoreHearingRequest;
use App\Models\Application;
use App\Models\Hearing;
use App\Services\Hearings\HearingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HearingController extends Controller
{
    public function __construct(private readonly HearingService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Hearing::class);
        $hearings = Hearing::query()->with(['candidate', 'application', 'provisionalList', 'definitiveList'])->latest()->paginate(20);

        return view('backoffice.hearings.index', compact('hearings'));
    }

    public function create(): View
    {
        Gate::authorize('create', Hearing::class);
        $applications = Application::query()->with('user')->latest()->limit(100)->get();

        return view('backoffice.hearings.create', ['applications' => $applications, 'types' => HearingType::options()]);
    }

    public function store(StoreHearingRequest $request): RedirectResponse
    {
        Gate::authorize('create', Hearing::class);
        $hearing = $this->service->create($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.hearings.show', $hearing)->with('success', 'Audiência criada.');
    }

    public function show(Hearing $hearing): View
    {
        Gate::authorize('view', $hearing);
        $hearing->load(['candidate', 'application', 'provisionalList', 'definitiveList', 'submissions.documentSubmission']);

        return view('backoffice.hearings.show', compact('hearing'));
    }

    public function issue(IssueHearingRequest $request, Hearing $hearing): RedirectResponse
    {
        Gate::authorize('update', $hearing);
        $this->service->issue($hearing, $this->authenticatedUser($request));

        return back()->with('success', 'Audiência emitida ao candidato.');
    }

    public function close(Request $request, Hearing $hearing): RedirectResponse
    {
        Gate::authorize('update', $hearing);
        $this->service->close($hearing, $this->authenticatedUser($request));

        return back()->with('success', 'Audiência fechada.');
    }

    public function cancel(Request $request, Hearing $hearing): RedirectResponse
    {
        Gate::authorize('update', $hearing);
        $this->service->cancel($hearing, $this->authenticatedUser($request));

        return back()->with('success', 'Audiência cancelada.');
    }
}
