<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreliminaryHearingSubmissionRequest;
use App\Models\HearingSubmission;
use App\Services\Hearings\HearingSubmissionService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PreliminaryHearingSubmissionController extends Controller
{
    public function __construct(
        private readonly HearingSubmissionService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', HearingSubmission::class);

        return view('backoffice.preliminary-hearings.index', [
            'submissions' => $this->municipalScope
                ->hearingSubmissions(
                    HearingSubmission::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['hearing', 'application', 'candidate'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(HearingSubmission $preliminaryHearingSubmission): View
    {
        Gate::authorize('viewBackoffice', $preliminaryHearingSubmission);

        return view('backoffice.preliminary-hearings.show', ['submission' => $preliminaryHearingSubmission]);
    }

    public function decide(UpdatePreliminaryHearingSubmissionRequest $request, HearingSubmission $preliminaryHearingSubmission): RedirectResponse
    {
        Gate::authorize('reviewBackoffice', $preliminaryHearingSubmission);
        $this->service->review($preliminaryHearingSubmission, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Pronúncia analisada.');
    }
}
