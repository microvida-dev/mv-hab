<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ComplaintDecisionResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveComplaintDecisionRequest;
use App\Http\Requests\StoreComplaintDecisionRequest;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Services\Complaints\ComplaintDecisionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintDecisionController extends Controller
{
    public function __construct(private readonly ComplaintDecisionService $service) {}

    public function create(Complaint $complaint): View
    {
        Gate::authorize('decideBackoffice', [ComplaintDecision::class, $complaint]);

        return view('backoffice.complaint-decisions.create', [
            'complaint' => $complaint,
            'results' => ComplaintDecisionResult::options(),
        ]);
    }

    public function store(StoreComplaintDecisionRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('decideBackoffice', [ComplaintDecision::class, $complaint]);
        $decision = $this->service->create($complaint, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.complaint-decisions.show', $decision)->with('success', 'Decisão criada.');
    }

    public function show(ComplaintDecision $complaintDecision): View
    {
        Gate::authorize('viewBackoffice', $complaintDecision);
        $complaintDecision->load(['complaint.candidate', 'application', 'provisionalList', 'proposedBy', 'approvedBy']);

        return view('backoffice.complaint-decisions.show', compact('complaintDecision'));
    }

    public function approve(ApproveComplaintDecisionRequest $request, ComplaintDecision $complaintDecision): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $complaintDecision);
        $this->service->approve($complaintDecision, $this->authenticatedUser($request));

        return back()->with('success', 'Decisão aprovada.');
    }

    public function cancel(Request $request, ComplaintDecision $complaintDecision): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $complaintDecision);
        $this->service->cancel($complaintDecision, $this->authenticatedUser($request));

        return back()->with('success', 'Decisão cancelada.');
    }
}
