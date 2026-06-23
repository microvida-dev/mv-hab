<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewHearingSubmissionRequest;
use App\Models\HearingSubmission;
use App\Services\Hearings\HearingSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class HearingSubmissionReviewController extends Controller
{
    public function __construct(private readonly HearingSubmissionService $service) {}

    public function show(HearingSubmission $hearingSubmission): View
    {
        Gate::authorize('view', $hearingSubmission);
        $hearingSubmission->load(['hearing', 'candidate', 'application', 'documentSubmission']);

        return view('backoffice.hearings.show', ['hearing' => $hearingSubmission->hearing]);
    }

    public function accept(ReviewHearingSubmissionRequest $request, HearingSubmission $hearingSubmission): RedirectResponse
    {
        Gate::authorize('review', $hearingSubmission);
        $data = $request->validated();
        $data['accepted'] = true;
        $this->service->review($hearingSubmission, $data, $this->authenticatedUser($request));

        return back()->with('success', 'Pronúncia aceite.');
    }

    public function reject(ReviewHearingSubmissionRequest $request, HearingSubmission $hearingSubmission): RedirectResponse
    {
        Gate::authorize('review', $hearingSubmission);
        $data = $request->validated();
        $data['accepted'] = false;
        $this->service->review($hearingSubmission, $data, $this->authenticatedUser($request));

        return back()->with('success', 'Pronúncia rejeitada.');
    }
}
