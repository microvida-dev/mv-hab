<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveRentReviewRequest;
use App\Http\Requests\CalculateRentReviewRequest;
use App\Http\Requests\RejectFinanceRecordRequest;
use App\Http\Requests\StoreRentReviewRequest;
use App\Models\RentReview;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\RentReviewService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RentReviewController extends Controller
{
    public function __construct(
        private readonly RentReviewService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', RentReview::class);
        $reviews = $this->municipalScope
            ->rentReviews(RentReview::query(), $this->currentUser())
            ->with(['tenant', 'tenantFinancialAccount'])
            ->latest()
            ->paginate(25);

        return view('backoffice.finance.rent-reviews.index', compact('reviews'));
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', RentReview::class);
        $accounts = $this->municipalScope
            ->tenantFinancialAccounts(TenantFinancialAccount::query(), $this->currentUser())
            ->with('tenant')
            ->orderBy('account_number')
            ->get();

        return view('backoffice.finance.rent-reviews.create', compact('accounts'));
    }

    public function store(StoreRentReviewRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', RentReview::class);
        $actor = $this->authenticatedUser($request);
        $account = $this->municipalScope
            ->tenantFinancialAccounts(TenantFinancialAccount::query(), $actor)
            ->whereKey((int) $request->validated('tenant_financial_account_id'))
            ->firstOrFail();
        $review = $this->service->store($account, $actor, $request->validated());

        return redirect()->route('backoffice.finance.rent-reviews.show', $review)->with('success', 'Revisão de renda criada.');
    }

    public function show(RentReview $rentReview): View
    {
        Gate::authorize('viewBackoffice', $rentReview);
        $rentReview->load(['tenant', 'tenantFinancialAccount', 'leaseContract', 'newRentSchedule', 'incomeChangeDeclarations']);

        return view('backoffice.finance.rent-reviews.show', compact('rentReview'));
    }

    public function calculate(CalculateRentReviewRequest $request, RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('calculateBackoffice', $rentReview);
        $this->service->calculate($rentReview, $this->authenticatedUser($request), $request->validated('proposed_rent'));

        return back()->with('success', 'Revisão calculada.');
    }

    public function approve(ApproveRentReviewRequest $request, RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $rentReview);
        $this->service->approve($rentReview, $this->authenticatedUser($request), $request->validated('approved_rent'));

        return back()->with('success', 'Revisão aprovada.');
    }

    public function reject(RejectFinanceRecordRequest $request, RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('rejectBackoffice', $rentReview);
        $this->service->reject($rentReview, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Revisão rejeitada.');
    }

    public function apply(RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('applyBackoffice', $rentReview);
        $this->service->apply($rentReview, $this->currentUser());

        return back()->with('success', 'Revisão aplicada ao contrato.');
    }
}
