<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCorrectionResponseRequest;
use App\Http\Requests\SubmitCorrectionResponseRequest;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\DocumentSubmission;
use App\Services\Administrative\CorrectionResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CorrectionResponseController extends Controller
{
    public function __construct(private readonly CorrectionResponseService $responseService) {}

    public function create(Request $request, CorrectionRequest $correctionRequest): View
    {
        Gate::authorize('create', [CorrectionResponse::class, $correctionRequest]);
        $correctionRequest->load(['items.responses', 'application']);

        return view('candidate.correction-requests.respond', [
            'correctionRequest' => $correctionRequest,
            'documents' => $this->candidateDocuments($request, $correctionRequest),
        ]);
    }

    public function store(StoreCorrectionResponseRequest $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        Gate::authorize('create', [CorrectionResponse::class, $correctionRequest]);
        $this->responseService->submit($correctionRequest, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.correction-requests.show', $correctionRequest)
            ->with('success', 'A sua resposta foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.');
    }

    public function edit(CorrectionResponse $correctionResponse): View
    {
        Gate::authorize('view', $correctionResponse);
        $correctionRequest = $correctionResponse->correctionRequest()->with(['items.responses', 'application'])->firstOrFail();

        return view('candidate.correction-requests.respond', [
            'correctionRequest' => $correctionRequest,
            'response' => $correctionResponse,
            'documents' => $this->candidateDocuments(request(), $correctionRequest),
        ]);
    }

    public function update(StoreCorrectionResponseRequest $request, CorrectionResponse $correctionResponse): RedirectResponse
    {
        Gate::authorize('view', $correctionResponse);
        $correctionRequest = $correctionResponse->correctionRequest()->first();
        abort_unless($correctionRequest instanceof CorrectionRequest, 500);

        $this->responseService->submit($correctionRequest, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.correction-requests.show', $correctionResponse->correctionRequest)
            ->with('success', 'Resposta atualizada.');
    }

    public function submit(SubmitCorrectionResponseRequest $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
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

        return to_route('candidate.correction-requests.show', $correctionRequest)
            ->with('success', 'A sua resposta foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.');
    }

    /**
     * @return Collection<int, DocumentSubmission>
     */
    private function candidateDocuments(Request $request, CorrectionRequest $correctionRequest): Collection
    {
        $application = $correctionRequest->application;
        abort_unless($application instanceof Application, 500);

        return DocumentSubmission::query()
            ->with('documentType')
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->where(function ($query) use ($application, $correctionRequest) {
                $query->where('application_id', $correctionRequest->application_id)
                    ->orWhere('adhesion_registration_id', $application->adhesion_registration_id);
            })
            ->latest()
            ->get();
    }
}
