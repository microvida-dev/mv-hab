<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignComplaintRequest;
use App\Models\Complaint;
use App\Models\User;
use App\Services\Complaints\ComplaintService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintController extends Controller
{
    public function __construct(private readonly ComplaintService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Complaint::class);
        $complaints = Complaint::query()->with(['candidate', 'application', 'provisionalList', 'assignedTo', 'decision'])->latest()->paginate(20);

        return view('backoffice.complaints.index', compact('complaints'));
    }

    public function show(Complaint $complaint): View
    {
        Gate::authorize('view', $complaint);
        $complaint->load(['candidate', 'application', 'provisionalListEntry', 'provisionalList', 'attachments.documentSubmission', 'reviews.reviewedBy', 'decision', 'additionalInformationRequests.responses']);
        $technicians = User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['administrator', 'municipal_technician', 'jury']))->orderBy('name')->get();

        return view('backoffice.complaints.show', compact('complaint', 'technicians'));
    }

    public function assign(AssignComplaintRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);
        $assignee = User::query()->findOrFail($request->integer('assigned_to'));
        $this->service->assign($complaint, $assignee, $this->authenticatedUser($request));

        return back()->with('success', 'Reclamação atribuída.');
    }

    public function markReceived(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);
        $this->service->markReceived($complaint, $this->authenticatedUser($request));

        return back()->with('success', 'Reclamação marcada como recebida.');
    }

    public function startReview(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);
        $this->service->startReview($complaint, $this->authenticatedUser($request));

        return back()->with('success', 'Análise iniciada.');
    }

    public function close(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);
        $this->service->close($complaint, $this->authenticatedUser($request));

        return back()->with('success', 'Reclamação fechada.');
    }
}
