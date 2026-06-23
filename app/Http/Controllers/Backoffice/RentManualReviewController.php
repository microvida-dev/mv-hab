<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveRentManualReviewRequest;
use App\Http\Requests\RejectRentManualReviewRequest;
use App\Http\Requests\StoreRentManualReviewRequest;
use App\Models\RentCalculation;
use App\Models\RentManualReview;
use App\Services\Contracts\RentManualReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RentManualReviewController extends Controller
{
    public function __construct(private readonly RentManualReviewService $service) {}

    public function store(StoreRentManualReviewRequest $request, RentCalculation $rentCalculation): RedirectResponse
    {
        Gate::authorize('create', RentManualReview::class);
        $this->service->request($rentCalculation, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Revisão manual solicitada.');
    }

    public function approve(ApproveRentManualReviewRequest $request, RentManualReview $rentManualReview): RedirectResponse
    {
        Gate::authorize('approve', $rentManualReview);
        $this->service->approve($rentManualReview, $this->authenticatedUser($request), $request->validated('approved_rent'), $request->validated('internal_notes'));

        return back()->with('success', 'Revisão manual aprovada.');
    }

    public function reject(RejectRentManualReviewRequest $request, RentManualReview $rentManualReview): RedirectResponse
    {
        Gate::authorize('approve', $rentManualReview);
        $this->service->reject($rentManualReview, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Revisão manual rejeitada.');
    }
}
