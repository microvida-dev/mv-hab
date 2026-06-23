<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ProvisionalListStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\SubmitComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Models\Complaint;
use App\Models\DocumentSubmission;
use App\Models\ProvisionalListEntry;
use App\Services\Complaints\ComplaintService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintController extends Controller
{
    public function __construct(private readonly ComplaintService $service) {}

    public function index(Request $request): View
    {
        $complaints = Complaint::query()
            ->with(['provisionalList', 'application', 'decision'])
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->latest()
            ->paginate(10);

        return view('candidate.complaints.index', compact('complaints'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Complaint::class);
        $entries = ProvisionalListEntry::query()
            ->with(['provisionalList.contest', 'application'])
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->whereHas('provisionalList', fn ($query) => $query->whereIn('status', [
                ProvisionalListStatus::Published->value,
                ProvisionalListStatus::ComplaintPeriodOpen->value,
                ProvisionalListStatus::ComplaintPeriodClosed->value,
            ]))
            ->latest()
            ->get();
        $documents = DocumentSubmission::query()->where('user_id', $this->authenticatedUser($request)->id)->latest()->get();

        return view('candidate.complaints.create', compact('entries', 'documents'));
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        Gate::authorize('create', Complaint::class);
        $complaint = $this->service->create($request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.complaints.show', $complaint)->with('success', 'Reclamação guardada como rascunho.');
    }

    public function show(Complaint $complaint): View
    {
        Gate::authorize('view', $complaint);
        $complaint->load(['provisionalList', 'application', 'attachments.documentSubmission', 'decision', 'additionalInformationRequests.responses']);

        return view('candidate.complaints.show', compact('complaint'));
    }

    public function edit(Complaint $complaint): View
    {
        Gate::authorize('update', $complaint);

        return view('candidate.complaints.edit', compact('complaint'));
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);
        $this->service->update($complaint, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.complaints.show', $complaint)->with('success', 'Reclamação atualizada.');
    }

    public function submit(SubmitComplaintRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('submit', $complaint);
        $this->service->submit($complaint, $this->authenticatedUser($request));

        return to_route('candidate.complaints.show', $complaint)->with('success', 'A sua reclamação foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.');
    }

    public function withdraw(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);
        $this->service->withdraw($complaint, $this->authenticatedUser($request));

        return back()->with('success', 'Reclamação desistida.');
    }
}
