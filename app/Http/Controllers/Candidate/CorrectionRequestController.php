<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = CorrectionRequest::query()
            ->with(['application', 'administrativeProcess', 'items', 'responses'])
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->where('candidate_visible', true)
            ->latest()
            ->paginate(10);

        return view('candidate.correction-requests.index', compact('requests'));
    }

    public function show(CorrectionRequest $correctionRequest): View
    {
        Gate::authorize('view', $correctionRequest);
        $correctionRequest->load(['administrativeProcess', 'application', 'items.responses.documentSubmission', 'responses.documentSubmission']);

        return view('candidate.correction-requests.show', ['correctionRequest' => $correctionRequest]);
    }
}
