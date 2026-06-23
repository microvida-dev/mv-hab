<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitApplicationRequest;
use App\Models\Application;
use App\Services\Applications\ApplicationSubmissionService;
use App\Services\Applications\ApplicationValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ApplicationSubmissionController extends Controller
{
    public function __construct(
        private readonly ApplicationValidationService $validationService,
        private readonly ApplicationSubmissionService $submissionService,
    ) {}

    public function review(Application $application): View
    {
        Gate::authorize('submit', $application);

        $application->load([
            'user',
            'contest.program',
            'adhesionRegistration',
            'household.members.incomeRecords.incomeSource',
            'household.incomeRecords.incomeSource',
            'currentHousingSituation',
        ]);
        $readiness = $this->validationService->readinessForSubmission($application);

        return view('candidate.applications.review', compact('application', 'readiness'));
    }

    public function submit(SubmitApplicationRequest $request, Application $application): RedirectResponse
    {
        $application = $this->submissionService->submit(
            $application,
            $this->authenticatedUser($request),
        );

        return to_route('candidate.applications.receipt', $application)
            ->with('success', 'A candidatura foi submetida com sucesso.');
    }
}
