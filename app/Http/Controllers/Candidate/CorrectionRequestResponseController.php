<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\RespondCorrectionRequestRequest;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Services\ApplicationActions\CorrectionRequestResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CorrectionRequestResponseController extends Controller
{
    public function __construct(private readonly CorrectionRequestResponseService $service) {}

    public function create(Application $application, CorrectionRequest $correctionRequest): View
    {
        Gate::authorize('view', $correctionRequest);
        abort_unless($correctionRequest->application_id === $application->id, 404);
        $correctionRequest->load('items');

        return view('candidate.correction-requests.respond', compact('application', 'correctionRequest'));
    }

    public function store(RespondCorrectionRequestRequest $request, Application $application, CorrectionRequest $correctionRequest): RedirectResponse
    {
        Gate::authorize('view', $correctionRequest);
        abort_unless($correctionRequest->application_id === $application->id, 404);
        $data = $request->validated();
        $data['response_text'] = $data['response_text'] ?? $data['message'];
        $this->service->submit($correctionRequest, $data, $this->authenticatedUser($request));

        return to_route('candidate.correction-requests.show', $correctionRequest)->with('success', 'Resposta submetida.');
    }
}
