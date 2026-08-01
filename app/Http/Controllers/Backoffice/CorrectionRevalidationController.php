<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectionResolutionRequest;
use App\Http\Requests\CorrectionRevalidationQueueRequest;
use App\Http\Requests\ReviewCorrectionRevalidationItemRequest;
use App\Http\Requests\StartCorrectionRevalidationRequest;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Services\Administrative\CorrectionResolutionService;
use App\Services\Administrative\CorrectionRevalidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CorrectionRevalidationController extends Controller
{
    public function __construct(
        private readonly CorrectionRevalidationService $revalidation,
        private readonly CorrectionResolutionService $resolution,
    ) {}

    public function index(
        CorrectionRevalidationQueueRequest $request,
    ): View {
        Gate::authorize(
            'viewRevalidationQueue',
            CorrectionRequest::class,
        );
        $queue = $this->revalidation->queue(
            $this->authenticatedUser($request),
            $request->filters(),
        );

        return view('backoffice.correction-revalidations.index', [
            ...$queue,
            'filters' => $request->filters(),
        ]);
    }

    public function start(
        StartCorrectionRevalidationRequest $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize(
            'startRevalidationBackoffice',
            $correctionRequest,
        );
        $this->revalidation->start(
            $correctionRequest,
            $this->authenticatedUser($request),
        );

        return to_route(
            'backoffice.correction-requests.show',
            $correctionRequest,
        )->with('success', 'Segunda análise diferencial iniciada.');
    }

    public function decide(
        ReviewCorrectionRevalidationItemRequest $request,
        CorrectionRequest $correctionRequest,
        CorrectionResponse $correctionResponse,
    ): RedirectResponse {
        Gate::authorize(
            'decideRevalidationBackoffice',
            $correctionResponse,
        );
        $payload = $request->payload();
        $this->revalidation->decide(
            request: $correctionRequest,
            response: $correctionResponse,
            result: $payload['result'],
            reviewNotes: $payload['review_notes'],
            sourceFingerprint: $payload['source_fingerprint'],
            expectedDecisionToken: $payload['expected_decision_token'],
            actor: $this->authenticatedUser($request),
        );

        return to_route(
            'backoffice.correction-requests.show',
            $correctionRequest,
        )->with('success', 'Decisão do elemento guardada.');
    }

    public function preview(
        CorrectionResolutionRequest $request,
        CorrectionRequest $correctionRequest,
    ): View {
        Gate::authorize(
            'previewRevalidationBackoffice',
            $correctionRequest,
        );
        $payload = $request->payload();

        return view('backoffice.correction-revalidations.preview', [
            'preview' => $this->resolution->preview(
                $correctionRequest,
                $this->authenticatedUser($request),
                $payload['reason'],
            ),
        ]);
    }

    public function seal(
        CorrectionResolutionRequest $request,
        CorrectionRequest $correctionRequest,
    ): RedirectResponse {
        Gate::authorize(
            'sealRevalidationBackoffice',
            $correctionRequest,
        );
        $payload = $request->payload();
        $batch = $this->resolution->seal(
            $correctionRequest,
            $this->authenticatedUser($request),
            $payload['reason'],
            (string) $payload['preview_token'],
        );

        return to_route(
            'backoffice.application-review-batches.show',
            $batch,
        )->with(
            'success',
            'A segunda análise foi selada com snapshot imutável.',
        );
    }
}
