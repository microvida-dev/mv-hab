<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Models\RentReview;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class RentReviewController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', RentReview::class);
        $reviews = RentReview::query()->where('user_id', $this->currentUser()->id)->latest()->paginate(20);

        return view('candidate.finance.rent-reviews.index', compact('reviews'));
    }

    public function show(RentReview $rentReview): View
    {
        Gate::authorize('view', $rentReview);
        $rentReview->load(['tenantFinancialAccount', 'newRentSchedule']);

        return view('candidate.finance.rent-reviews.show', compact('rentReview'));
    }
}
