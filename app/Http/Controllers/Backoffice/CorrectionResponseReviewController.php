<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewCorrectionResponseRequest;
use App\Models\CorrectionResponse;
use App\Services\Administrative\CorrectionResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CorrectionResponseReviewController extends Controller
{
    public function __construct(private readonly CorrectionResponseService $responseService) {}

    public function show(CorrectionResponse $correctionResponse): View
    {
        Gate::authorize('view', $correctionResponse);
        $correctionResponse->load(['correctionRequest', 'correctionRequestItem', 'application', 'candidate', 'documentSubmission', 'reviewedBy']);

        return view('backoffice.correction-responses.show', ['response' => $correctionResponse]);
    }

    public function accept(ReviewCorrectionResponseRequest $request, CorrectionResponse $correctionResponse): RedirectResponse
    {
        Gate::authorize('review', $correctionResponse);
        $this->responseService->accept($correctionResponse, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Resposta aceite.');
    }

    public function reject(ReviewCorrectionResponseRequest $request, CorrectionResponse $correctionResponse): RedirectResponse
    {
        Gate::authorize('review', $correctionResponse);
        $this->responseService->reject($correctionResponse, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Resposta rejeitada.');
    }

    public function requestMoreInformation(ReviewCorrectionResponseRequest $request, CorrectionResponse $correctionResponse): RedirectResponse
    {
        Gate::authorize('review', $correctionResponse);
        $this->responseService->requestMoreInformation($correctionResponse, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Pedido de mais informação registado.');
    }
}
