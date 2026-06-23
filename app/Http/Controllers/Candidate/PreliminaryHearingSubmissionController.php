<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePreliminaryHearingSubmissionRequest;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Services\ApplicationActions\PreliminaryHearingSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PreliminaryHearingSubmissionController extends Controller
{
    public function __construct(private readonly PreliminaryHearingSubmissionService $service) {}

    public function create(Hearing $hearing): View
    {
        Gate::authorize('view', $hearing);

        return view('candidate.preliminary-hearings.create', compact('hearing'));
    }

    public function store(StorePreliminaryHearingSubmissionRequest $request, Hearing $hearing): RedirectResponse
    {
        $this->service->submit($hearing, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.hearings.show', $hearing)->with('success', 'Pronúncia submetida.');
    }

    public function show(HearingSubmission $preliminaryHearingSubmission): View
    {
        Gate::authorize('view', $preliminaryHearingSubmission);

        return view('candidate.preliminary-hearings.show', ['submission' => $preliminaryHearingSubmission]);
    }
}
