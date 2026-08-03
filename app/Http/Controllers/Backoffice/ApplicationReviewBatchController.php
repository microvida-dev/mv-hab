<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationReviewBatchRequest;
use App\Models\ApplicationReviewBatch;
use App\Models\Contest;
use App\Services\Administrative\ApplicationReviewBatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationReviewBatchController extends Controller
{
    public function __construct(
        private readonly ApplicationReviewBatchService $batchService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ApplicationReviewBatch::class);
        $user = $this->authenticatedUser($request);

        return view('backoffice.application-review-batches.index', [
            'contests' => $this->batchService->contestOverview($user),
        ]);
    }

    public function contest(Request $request, Contest $contest): View
    {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'sealForContest',
            [ApplicationReviewBatch::class, $contest],
        );
        $inspection = $this->batchService->inspectContest(
            $contest,
            $user,
        );

        return view('backoffice.application-review-batches.contest', [
            'contest' => $contest,
            'inspection' => $inspection,
            'batches' => $this->batchService->batchesForContest(
                $contest,
                $user,
            ),
        ]);
    }

    public function preview(
        ApplicationReviewBatchRequest $request,
        Contest $contest,
    ): View {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'sealForContest',
            [ApplicationReviewBatch::class, $contest],
        );

        return view('backoffice.application-review-batches.preview', [
            'contest' => $contest,
            'preview' => $this->batchService->preview(
                $contest,
                $user,
                $request->payload(),
            ),
        ]);
    }

    public function seal(
        ApplicationReviewBatchRequest $request,
        Contest $contest,
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'sealForContest',
            [ApplicationReviewBatch::class, $contest],
        );
        $batch = $this->batchService->seal(
            $contest,
            $user,
            $request->payload(),
        );

        return to_route(
            'backoffice.application-review-batches.show',
            $batch,
        )->with(
            'success',
            'O lote de revisão foi selado com snapshots imutáveis.',
        );
    }

    public function show(
        Request $request,
        ApplicationReviewBatch $applicationReviewBatch,
    ): View {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'view',
            $applicationReviewBatch,
        );
        $applicationReviewBatch->load([
            'contest.program',
            'correctionRequest',
            'publication',
            'sealedBy',
            'items',
        ]);

        return view('backoffice.application-review-batches.show', [
            'batch' => $applicationReviewBatch,
        ]);
    }
}
