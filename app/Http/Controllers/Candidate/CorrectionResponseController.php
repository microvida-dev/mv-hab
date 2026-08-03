<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCorrectionResponseRequest;
use App\Http\Requests\SubmitCorrectionResponseRequest;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\CorrectionResponse;
use App\Models\DocumentSubmission;
use App\Services\Administrative\CandidateCorrectionWorkspaceService;
use App\Services\Administrative\CorrectionResponseService;
use App\Services\Administrative\CorrectionSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CorrectionResponseController extends Controller
{
    public function __construct(
        private readonly CorrectionResponseService $responseService,
        private readonly CandidateCorrectionWorkspaceService $workspace,
        private readonly CorrectionSubmissionService $submissions,
    ) {}

    public function create(
        Request $request,
        CorrectionRequest $correctionRequest,
    ): View|RedirectResponse {
        Gate::authorize(
            'create',
            [CorrectionResponse::class, $correctionRequest],
        );

        if (! $correctionRequest->isLegacy()) {
            return to_route(
                'candidate.correction-requests.show',
                $correctionRequest,
            );
        }

        $correctionRequest->load(['items.responses', 'application']);

        return view('candidate.correction-requests.respond', [
            'correctionRequest' => $correctionRequest,
            'documents' => $this->candidateDocuments(
                $request,
                $correctionRequest,
            ),
        ]);
    }

    public function store(
        StoreCorrectionResponseRequest $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize(
            'create',
            [CorrectionResponse::class, $correctionRequest],
        );

        $data = $request->validated();
        $item = $correctionRequest->items()
            ->whereKey((int) $data['correction_request_item_id'])
            ->first();

        if (! $item instanceof CorrectionRequestItem) {
            abort(404);
        }

        if ($correctionRequest->isLegacy()) {
            $this->responseService->submit(
                $correctionRequest,
                $data,
                $this->authenticatedUser($request),
            );

            return to_route(
                'candidate.correction-requests.show',
                $correctionRequest,
            )->with(
                'success',
                'A sua resposta foi submetida com sucesso.',
            );
        }

        $this->workspace->save(
            request: $correctionRequest,
            item: $item,
            data: $data,
            file: $request->file('file'),
            candidate: $this->authenticatedUser($request),
        );

        return to_route(
            'candidate.correction-requests.show',
            $correctionRequest,
        )->with(
            'success',
            'Elemento guardado. Nenhuma notificação municipal foi enviada; conclua primeiro toda a checklist.',
        );
    }

    public function edit(
        CorrectionResponse $correctionResponse,
    ): View|RedirectResponse {
        Gate::authorize('view', $correctionResponse);

        $correctionRequest = $correctionResponse
            ->correctionRequest()
            ->with(['items.responses', 'application'])
            ->firstOrFail();

        if (! $correctionRequest->isLegacy()) {
            return to_route(
                'candidate.correction-requests.show',
                $correctionRequest,
            );
        }

        return view('candidate.correction-requests.respond', [
            'correctionRequest' => $correctionRequest,
            'response' => $correctionResponse,
            'documents' => $this->candidateDocuments(
                request(),
                $correctionRequest,
            ),
        ]);
    }

    public function update(
        StoreCorrectionResponseRequest $request,
        CorrectionResponse $correctionResponse,
    ): RedirectResponse {
        Gate::authorize('view', $correctionResponse);

        $correctionRequest = $correctionResponse
            ->correctionRequest()
            ->first();

        abort_unless(
            $correctionRequest instanceof CorrectionRequest,
            500,
        );

        if (! $correctionRequest->isLegacy()) {
            $data = $request->validated();
            $item = $correctionRequest->items()
                ->whereKey((int) $data['correction_request_item_id'])
                ->firstOrFail();

            $this->workspace->save(
                request: $correctionRequest,
                item: $item,
                data: $data,
                file: $request->file('file'),
                candidate: $this->authenticatedUser($request),
            );
        } else {
            $this->responseService->submit(
                $correctionRequest,
                $request->validated(),
                $this->authenticatedUser($request),
            );
        }

        return to_route(
            'candidate.correction-requests.show',
            $correctionRequest,
        )->with('success', 'Elemento atualizado.');
    }

    public function submit(
        SubmitCorrectionResponseRequest $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        if (! $correctionRequest->isLegacy()) {
            Gate::authorize('submit', $correctionRequest);

            $receipt = $this->submissions->submit(
                $correctionRequest,
                $this->authenticatedUser($request),
            );

            return to_route(
                'candidate.correction-requests.receipt',
                $correctionRequest,
            )->with(
                'success',
                'Aperfeiçoamento submetido formalmente. Recibo '
                    .$receipt->receipt_number
                    .' emitido.',
            );
        }

        Gate::authorize('view', $correctionRequest);

        $pending = $correctionRequest->items()
            ->where('is_required', true)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'items' => 'Responda a todos os itens obrigatórios antes de concluir o pedido.',
            ]);
        }

        return to_route(
            'candidate.correction-requests.show',
            $correctionRequest,
        )->with(
            'success',
            'A sua resposta foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.',
        );
    }

    /** @return Collection<int, DocumentSubmission> */
    private function candidateDocuments(
        Request $request,
        CorrectionRequest $correctionRequest,
    ): Collection {
        $application = $correctionRequest->getRelationValue(
            'application',
        );

        abort_unless($application instanceof Application, 500);

        return DocumentSubmission::query()
            ->with('documentType')
            ->where(
                'user_id',
                $this->authenticatedUser($request)->id,
            )
            ->where(function ($query) use (
                $application,
                $correctionRequest,
            ): void {
                $query
                    ->where(
                        'application_id',
                        $correctionRequest->application_id,
                    )
                    ->orWhere(
                        'adhesion_registration_id',
                        $application->adhesion_registration_id,
                    );
            })
            ->latest()
            ->get();
    }
}
