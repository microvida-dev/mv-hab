<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequiredAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendCorrectionDeadlineRequest;
use App\Http\Requests\IssueCorrectionRequestRequest;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Http\Requests\UpdateCorrectionRequestRequest;
use App\Models\AdministrativeProcess;
use App\Models\CorrectionRequest;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use App\Services\Administrative\CorrectionDeadlineExtensionService;
use App\Services\Administrative\CorrectionProgressMetricsService;
use App\Services\Administrative\CorrectionRequestService;
use App\Services\Administrative\CorrectionRevalidationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CorrectionRequestController extends Controller
{
    public function __construct(
        private readonly CorrectionRequestService $correctionRequestService,
        private readonly CorrectionDeadlineExtensionService $deadlineExtensions,
        private readonly CorrectionProgressMetricsService $progress,
        private readonly CorrectionRevalidationService $revalidation,
    ) {}

    public function index(
        Request $request,
        AdministrativeProcess $administrativeProcess,
    ): View {
        Gate::authorize('viewBackoffice', $administrativeProcess);

        $summaryRequests = $administrativeProcess
            ->correctionRequests()
            ->with('items')
            ->get();

        $requests = $administrativeProcess
            ->correctionRequests()
            ->with('items')
            ->latest()
            ->paginate(20);

        return view(
            'backoffice.correction-requests.index',
            [
                'administrativeProcess' => $administrativeProcess,
                'requests' => $requests,
                'progressByRequest' => $this->progress
                    ->forRequests($requests->getCollection()),
                'requestSummary' => $this->progress
                    ->summarize($summaryRequests),
            ],
        );
    }

    public function create(
        Request $request,
        AdministrativeProcess $administrativeProcess,
    ): View {
        Gate::authorize('createBackoffice', $administrativeProcess);

        return view('backoffice.correction-requests.create', [
            'process' => $administrativeProcess,
            'issueTypes' => CorrectionIssueType::options(),
            'actions' => CorrectionRequiredAction::options(),
            'documentTypes' => DocumentType::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'requiredDocuments' => RequiredDocument::query()
                ->with('documentType')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(
        StoreCorrectionRequestRequest $request,
        AdministrativeProcess $administrativeProcess,
    ): RedirectResponse {
        Gate::authorize('createBackoffice', $administrativeProcess);
        $correctionRequest = $this->correctionRequestService
            ->create(
                $administrativeProcess,
                $request->validated(),
                $this->authenticatedUser($request),
            );

        return to_route(
            'backoffice.correction-requests.show',
            $correctionRequest,
        )->with(
            'success',
            'Pedido de aperfeiçoamento criado.',
        );
    }

    public function show(
        Request $request,
        CorrectionRequest $correctionRequest,
    ): View {
        Gate::authorize('viewBackoffice', $correctionRequest);
        $correctionRequest->load([
            'administrativeProcess',
            'application',
            'candidate',
            'issuedBy',
            'items.documentType',
            'items.requiredDocument',
            'responses.correctionRequestItem',
            'responses.documentSubmission',
            'responses.reviewedBy',
            'deadlineExtensions.authorizedBy',
            'submissionReceipt',
        ]);
        $revalidationWorkspace = ! $correctionRequest->isLegacy()
            && (
                $correctionRequest->submitted_at !== null
                || $correctionRequest->revalidation_started_at !== null
                || $correctionRequest->revalidationBatch()->exists()
            )
                ? $this->revalidation->workspace(
                    $correctionRequest,
                    $this->authenticatedUser($request),
                )
                : null;

        return view(
            'backoffice.correction-requests.show',
            [
                'correctionRequest' => $correctionRequest,
                'requestProgress' => $this->progress->progress(
                    $correctionRequest,
                ),
                'revalidationWorkspace' => $revalidationWorkspace,
            ],
        );
    }

    public function edit(
        Request $request,
        CorrectionRequest $correctionRequest,
    ): View {
        Gate::authorize('updateBackoffice', $correctionRequest);

        return view(
            'backoffice.correction-requests.edit',
            ['correctionRequest' => $correctionRequest],
        );
    }

    public function update(
        UpdateCorrectionRequestRequest $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize('updateBackoffice', $correctionRequest);
        $this->correctionRequestService->update(
            $correctionRequest,
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return to_route(
            'backoffice.correction-requests.show',
            $correctionRequest,
        )->with('success', 'Pedido atualizado.');
    }

    public function issue(
        IssueCorrectionRequestRequest $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize('issueBackoffice', $correctionRequest);
        $this->correctionRequestService->issue(
            $correctionRequest,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Pedido emitido ao candidato.',
        );
    }

    public function cancel(
        Request $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize('cancelBackoffice', $correctionRequest);
        $this->correctionRequestService->cancel(
            $correctionRequest,
            $this->authenticatedUser($request),
        );

        return back()->with('success', 'Pedido cancelado.');
    }

    public function close(
        Request $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize('completeBackoffice', $correctionRequest);
        $this->correctionRequestService->close(
            $correctionRequest,
            $this->authenticatedUser($request),
        );

        return back()->with('success', 'Pedido fechado.');
    }

    public function extendDeadline(
        ExtendCorrectionDeadlineRequest $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize(
            'extendDeadlineBackoffice',
            $correctionRequest,
        );
        $data = $request->validated();

        $this->deadlineExtensions->extend(
            request: $correctionRequest,
            extendedDeadline: CarbonImmutable::parse(
                (string) $data['extended_deadline_at'],
            ),
            reason: (string) $data['reason'],
            actor: $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Prazo prorrogado e registado em auditoria.',
        );
    }

    public function markOverdue(
        Request $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize(
            'markOverdueBackoffice',
            $correctionRequest,
        );
        $this->correctionRequestService->markOverdue(
            $correctionRequest,
            $this->authenticatedUser($request),
        );

        return back()->with(
            'success',
            'Pedido marcado como vencido.',
        );
    }
}
