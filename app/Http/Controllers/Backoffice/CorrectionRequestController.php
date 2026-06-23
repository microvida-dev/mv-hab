<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequiredAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\IssueCorrectionRequestRequest;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Http\Requests\UpdateCorrectionRequestRequest;
use App\Models\AdministrativeProcess;
use App\Models\CorrectionRequest;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use App\Services\Administrative\CorrectionRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CorrectionRequestController extends Controller
{
    public function __construct(private readonly CorrectionRequestService $correctionRequestService) {}

    public function index(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('view', $administrativeProcess);
        $requests = $administrativeProcess->correctionRequests()->with(['items', 'responses'])->latest()->paginate(20);

        return view('backoffice.correction-requests.index', compact('administrativeProcess', 'requests'));
    }

    public function create(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('create', CorrectionRequest::class);

        return view('backoffice.correction-requests.create', [
            'process' => $administrativeProcess,
            'issueTypes' => CorrectionIssueType::options(),
            'actions' => CorrectionRequiredAction::options(),
            'documentTypes' => DocumentType::query()->orderBy('name')->get(['id', 'name']),
            'requiredDocuments' => RequiredDocument::query()->with('documentType')->orderBy('id')->get(),
        ]);
    }

    public function store(StoreCorrectionRequestRequest $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('create', CorrectionRequest::class);
        $correctionRequest = $this->correctionRequestService->create($administrativeProcess, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.correction-requests.show', $correctionRequest)
            ->with('success', 'Pedido de aperfeiçoamento criado.');
    }

    public function show(CorrectionRequest $correctionRequest): View
    {
        Gate::authorize('view', $correctionRequest);
        $correctionRequest->load(['administrativeProcess', 'application', 'candidate', 'issuedBy', 'items.documentType', 'items.requiredDocument', 'responses.correctionRequestItem', 'responses.documentSubmission', 'responses.reviewedBy']);

        return view('backoffice.correction-requests.show', ['correctionRequest' => $correctionRequest]);
    }

    public function edit(CorrectionRequest $correctionRequest): View
    {
        Gate::authorize('update', $correctionRequest);

        return view('backoffice.correction-requests.edit', ['correctionRequest' => $correctionRequest]);
    }

    public function update(UpdateCorrectionRequestRequest $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        Gate::authorize('update', $correctionRequest);
        $this->correctionRequestService->update($correctionRequest, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.correction-requests.show', $correctionRequest)
            ->with('success', 'Pedido atualizado.');
    }

    public function issue(IssueCorrectionRequestRequest $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        Gate::authorize('update', $correctionRequest);
        $this->correctionRequestService->issue($correctionRequest, $this->authenticatedUser($request));

        return back()->with('success', 'Pedido emitido ao candidato.');
    }

    public function cancel(Request $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        Gate::authorize('update', $correctionRequest);
        $this->correctionRequestService->cancel($correctionRequest, $this->authenticatedUser($request));

        return back()->with('success', 'Pedido cancelado.');
    }

    public function close(Request $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        Gate::authorize('update', $correctionRequest);
        $this->correctionRequestService->close($correctionRequest, $this->authenticatedUser($request));

        return back()->with('success', 'Pedido fechado.');
    }

    public function markOverdue(Request $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        Gate::authorize('update', $correctionRequest);
        $this->correctionRequestService->markOverdue($correctionRequest, $this->authenticatedUser($request));

        return back()->with('success', 'Pedido marcado como vencido.');
    }
}
