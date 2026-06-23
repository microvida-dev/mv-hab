<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreliminaryHearingSubmissionRequest;
use App\Models\HearingSubmission;
use App\Services\Hearings\HearingSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PreliminaryHearingSubmissionController extends Controller
{
    public function __construct(private readonly HearingSubmissionService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', HearingSubmission::class);

        return view('backoffice.preliminary-hearings.index', [
            'submissions' => HearingSubmission::query()->with(['hearing', 'application', 'candidate'])->latest()->paginate(20),
        ]);
    }

    public function show(HearingSubmission $preliminaryHearingSubmission): View
    {
        Gate::authorize('view', $preliminaryHearingSubmission);

        return view('backoffice.preliminary-hearings.show', ['submission' => $preliminaryHearingSubmission]);
    }

    public function decide(UpdatePreliminaryHearingSubmissionRequest $request, HearingSubmission $preliminaryHearingSubmission): RedirectResponse
    {
        Gate::authorize('review', $preliminaryHearingSubmission);
        $this->service->review($preliminaryHearingSubmission, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Pronúncia analisada.');
    }
}
