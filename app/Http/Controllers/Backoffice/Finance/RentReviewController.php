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
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RentReviewController extends Controller
{
    public function __construct(private readonly RentReviewService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', RentReview::class);
        $reviews = RentReview::query()->with(['tenant', 'tenantFinancialAccount'])->latest()->paginate(25);

        return view('backoffice.finance.rent-reviews.index', compact('reviews'));
    }

    public function create(): View
    {
        Gate::authorize('create', RentReview::class);
        $accounts = TenantFinancialAccount::query()->with('tenant')->orderBy('account_number')->get();

        return view('backoffice.finance.rent-reviews.create', compact('accounts'));
    }

    public function store(StoreRentReviewRequest $request): RedirectResponse
    {
        Gate::authorize('create', RentReview::class);
        $account = TenantFinancialAccount::query()->whereKey((int) $request->validated('tenant_financial_account_id'))->firstOrFail();
        $review = $this->service->store($account, $this->authenticatedUser($request), $request->validated());

        return redirect()->route('backoffice.finance.rent-reviews.show', $review)->with('success', 'Revisão de renda criada.');
    }

    public function show(RentReview $rentReview): View
    {
        Gate::authorize('view', $rentReview);
        $rentReview->load(['tenant', 'tenantFinancialAccount', 'leaseContract', 'newRentSchedule', 'incomeChangeDeclarations']);

        return view('backoffice.finance.rent-reviews.show', compact('rentReview'));
    }

    public function calculate(CalculateRentReviewRequest $request, RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('update', $rentReview);
        $this->service->calculate($rentReview, $this->authenticatedUser($request), $request->validated('proposed_rent'));

        return back()->with('success', 'Revisão calculada.');
    }

    public function approve(ApproveRentReviewRequest $request, RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('update', $rentReview);
        $this->service->approve($rentReview, $this->authenticatedUser($request), (float) $request->validated('approved_rent'));

        return back()->with('success', 'Revisão aprovada.');
    }

    public function reject(RejectFinanceRecordRequest $request, RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('update', $rentReview);
        $this->service->reject($rentReview, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Revisão rejeitada.');
    }

    public function apply(RentReview $rentReview): RedirectResponse
    {
        Gate::authorize('update', $rentReview);
        $this->service->apply($rentReview, $this->currentUser());

        return back()->with('success', 'Revisão aplicada ao contrato.');
    }
}
