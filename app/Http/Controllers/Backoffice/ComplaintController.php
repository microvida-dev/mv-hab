<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignComplaintRequest;
use App\Models\Complaint;
use App\Models\User;
use App\Services\Complaints\ComplaintService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintController extends Controller
{
    public function __construct(
        private readonly ComplaintService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', Complaint::class);
        $complaints = $this->municipalScope
            ->complaints(Complaint::query(), $this->authenticatedUser($request))
            ->with(['candidate', 'application', 'provisionalList', 'assignedTo', 'decision'])
            ->latest()
            ->paginate(20);

        return view('backoffice.complaints.index', compact('complaints'));
    }

    public function show(Request $request, Complaint $complaint): View
    {
        Gate::authorize('viewBackoffice', $complaint);
        $complaint->load(['candidate', 'application', 'provisionalListEntry', 'provisionalList', 'attachments.documentSubmission', 'reviews.reviewedBy', 'decision', 'additionalInformationRequests.responses']);
        $technicians = $this->municipalScope
            ->users(User::query(), $this->authenticatedUser($request))
            ->where(function ($query): void {
                $query
                    ->whereHas('roles', fn ($roles) => $roles->where('name', 'administrator'))
                    ->orWhereHas(
                        'roles.permissions',
                        fn ($permissions) => $permissions->where('name', 'complaints.assign'),
                    );
            })
            ->orderBy('name')
            ->get();

        return view('backoffice.complaints.show', compact('complaint', 'technicians'));
    }

    public function assign(AssignComplaintRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('assignBackoffice', $complaint);
        $assignee = $this->municipalScope
            ->users(User::query(), $this->authenticatedUser($request))
            ->findOrFail($request->integer('assigned_to'));
        $this->service->assign($complaint, $assignee, $this->authenticatedUser($request));

        return back()->with('success', 'Reclamação atribuída.');
    }

    public function markReceived(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('markReceivedBackoffice', $complaint);
        $this->service->markReceived($complaint, $this->authenticatedUser($request));

        return back()->with('success', 'Reclamação marcada como recebida.');
    }

    public function startReview(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('reviewBackoffice', $complaint);
        $this->service->startReview($complaint, $this->authenticatedUser($request));

        return back()->with('success', 'Análise iniciada.');
    }

    public function close(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('closeBackoffice', $complaint);
        $this->service->close($complaint, $this->authenticatedUser($request));

        return back()->with('success', 'Reclamação fechada.');
    }
}
