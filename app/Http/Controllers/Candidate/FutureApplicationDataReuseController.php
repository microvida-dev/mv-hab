<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmFutureApplicationDataReuseRequest;
use App\Http\Requests\StoreFutureApplicationDataReuseRequest;
use App\Models\Application;
use App\Models\CandidateDataReuseProfile;
use App\Models\FutureApplicationDataReuse;
use App\Services\DataReuse\DataReuseEligibilityService;
use App\Services\DataReuse\FutureApplicationDataReuseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FutureApplicationDataReuseController extends Controller
{
    public function __construct(
        private readonly FutureApplicationDataReuseService $service,
        private readonly DataReuseEligibilityService $eligibility,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        Gate::authorize('create', FutureApplicationDataReuse::class);

        return view('candidate.data-reuse.index', [
            'profiles' => CandidateDataReuseProfile::query()->where('user_id', $user->id)->latest()->get(),
            'applications' => Application::query()->where('user_id', $user->id)->where('status', 'draft')->latest()->get(),
            'reuses' => FutureApplicationDataReuse::query()->where('user_id', $user->id)->latest()->get(),
            'eligibility' => $this->eligibility,
        ]);
    }

    public function store(StoreFutureApplicationDataReuseRequest $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        Gate::authorize('create', FutureApplicationDataReuse::class);
        $profileId = (int) $request->validated('source_reuse_profile_id');
        $applicationId = $request->validated('target_application_id');
        $profile = CandidateDataReuseProfile::query()->where('user_id', $user->id)->findOrFail($profileId);
        $application = $applicationId
            ? Application::query()->where('user_id', $user->id)->findOrFail((int) $applicationId)
            : null;

        $this->service->create($user, $profile, $application, $request->validated('sections'));

        return to_route('candidate.data-reuse.index')->with('success', 'Reutilização preparada para confirmação.');
    }

    public function confirm(ConfirmFutureApplicationDataReuseRequest $request, FutureApplicationDataReuse $futureApplicationDataReuse): RedirectResponse
    {
        Gate::authorize('update', $futureApplicationDataReuse);
        $applicationId = (int) $request->validated('target_application_id');
        $application = Application::query()
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->findOrFail($applicationId);

        $this->service->confirm($futureApplicationDataReuse, $this->authenticatedUser($request), $application);

        return to_route('candidate.data-reuse.index')->with('success', 'Dados confirmados para reutilização.');
    }
}
