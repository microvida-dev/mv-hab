<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitHearingSubmissionRequest;
use App\Models\DocumentSubmission;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Services\Hearings\HearingSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class HearingSubmissionController extends Controller
{
    public function __construct(private readonly HearingSubmissionService $service) {}

    public function create(Hearing $hearing): View
    {
        Gate::authorize('view', $hearing);
        $documents = DocumentSubmission::query()->where('user_id', auth()->id())->latest()->get();

        return view('candidate.hearings.submit', compact('hearing', 'documents'));
    }

    public function store(SubmitHearingSubmissionRequest $request, Hearing $hearing): RedirectResponse
    {
        Gate::authorize('create', HearingSubmission::class);
        $this->service->submit($hearing, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.hearings.show', $hearing)->with('success', 'Pronúncia submetida.');
    }
}
