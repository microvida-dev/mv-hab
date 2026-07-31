<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationReviewPublicationRequest;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewPublication;
use App\Services\Administrative\ApplicationReviewPublicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationReviewPublicationController extends Controller
{
    public function __construct(
        private readonly ApplicationReviewPublicationService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ApplicationReviewPublication::class);

        return view('backoffice.application-review-publications.index', [
            'publications' => $this->service->paginate(
                $this->authenticatedUser($request),
            ),
        ]);
    }

    public function create(
        Request $request,
        ApplicationReviewBatch $applicationReviewBatch,
    ): View|RedirectResponse {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'publishForBatch',
            [ApplicationReviewPublication::class, $applicationReviewBatch],
        );
        $existing = $this->service->existingForBatch(
            $applicationReviewBatch,
        );

        if ($existing instanceof ApplicationReviewPublication) {
            return to_route(
                'backoffice.application-review-publications.show',
                $existing,
            );
        }

        $applicationReviewBatch->load([
            'contest.program',
            'sealedBy',
            'items',
        ]);

        return view('backoffice.application-review-publications.create', [
            'batch' => $applicationReviewBatch,
        ]);
    }

    public function preview(
        ApplicationReviewPublicationRequest $request,
        ApplicationReviewBatch $applicationReviewBatch,
    ): View {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'publishForBatch',
            [ApplicationReviewPublication::class, $applicationReviewBatch],
        );
        $payload = $request->payload();

        return view('backoffice.application-review-publications.preview', [
            'preview' => $this->service->preview(
                $applicationReviewBatch,
                $user,
                $payload['reason'],
            ),
        ]);
    }

    public function publish(
        ApplicationReviewPublicationRequest $request,
        ApplicationReviewBatch $applicationReviewBatch,
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'publishForBatch',
            [ApplicationReviewPublication::class, $applicationReviewBatch],
        );
        $publication = $this->service->publish(
            $applicationReviewBatch,
            $user,
            $request->payload(),
        );

        return to_route(
            'backoffice.application-review-publications.show',
            $publication,
        )->with(
            'success',
            'Os resultados foram publicados coletivamente e as entregas ficaram registadas.',
        );
    }

    public function show(
        Request $request,
        ApplicationReviewPublication $applicationReviewPublication,
    ): View {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'view',
            $applicationReviewPublication,
        );
        $applicationReviewPublication->load([
            'batch',
            'contest.program',
            'publishedBy',
            'results.officialNotification',
            'results.inAppDelivery',
            'results.emailDelivery',
        ]);

        return view('backoffice.application-review-publications.show', [
            'publication' => $applicationReviewPublication,
        ]);
    }
}
