<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteApplicationReviewRequest;
use App\Http\Requests\StoreApplicationReviewRequest;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Services\Administrative\ApplicationReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ApplicationReviewController extends Controller
{
    public function __construct(private readonly ApplicationReviewService $reviewService) {}

    public function create(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('create', ApplicationReview::class);

        return view('backoffice.application-reviews.create', [
            'process' => $administrativeProcess,
            'types' => ApplicationReviewType::options(),
            'results' => ApplicationReviewResult::options(),
        ]);
    }

    public function store(StoreApplicationReviewRequest $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('create', ApplicationReview::class);
        $review = $this->reviewService->create($administrativeProcess, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.application-reviews.show', $review)
            ->with('success', 'Análise criada.');
    }

    public function show(ApplicationReview $applicationReview): View
    {
        Gate::authorize('view', $applicationReview);
        $applicationReview->load(['administrativeProcess', 'application', 'reviewedBy', 'items']);

        return view('backoffice.application-reviews.show', [
            'review' => $applicationReview,
            'results' => ApplicationReviewResult::options(),
        ]);
    }

    public function complete(CompleteApplicationReviewRequest $request, ApplicationReview $applicationReview): RedirectResponse
    {
        Gate::authorize('update', $applicationReview);
        $this->reviewService->complete($applicationReview, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.application-reviews.show', $applicationReview)
            ->with('success', 'Análise concluída.');
    }
}
