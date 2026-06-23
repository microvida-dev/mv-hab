<?php

namespace App\Http\Controllers\Candidate\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomeChangeDeclarationRequest;
use App\Models\IncomeChangeDeclaration;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\IncomeChangeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IncomeChangeDeclarationController extends Controller
{
    public function __construct(private readonly IncomeChangeService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', IncomeChangeDeclaration::class);
        $declarations = IncomeChangeDeclaration::query()->where('user_id', $this->currentUser()->id)->latest()->paginate(20);

        return view('candidate.finance.income-changes.index', compact('declarations'));
    }

    public function create(): View
    {
        Gate::authorize('create', IncomeChangeDeclaration::class);
        $accounts = TenantFinancialAccount::query()->where('user_id', $this->currentUser()->id)->get();

        return view('candidate.finance.income-changes.create', compact('accounts'));
    }

    public function store(StoreIncomeChangeDeclarationRequest $request): RedirectResponse
    {
        Gate::authorize('create', IncomeChangeDeclaration::class);

        $validated = $request->validated();

        $account = TenantFinancialAccount::query()
            ->findOrFail((int) $validated['tenant_financial_account_id']);

        $declaration = $this->service->store(
            $account,
            $this->authenticatedUser($request),
            $validated,
        );

        return redirect()
            ->route('candidate.finance.income-changes.show', $declaration)
            ->with('success', 'Declaração criada.');
    }

    public function show(IncomeChangeDeclaration $incomeChangeDeclaration): View
    {
        Gate::authorize('view', $incomeChangeDeclaration);
        $incomeChangeDeclaration->load(['tenantFinancialAccount', 'rentReview']);

        return view('candidate.finance.income-changes.show', compact('incomeChangeDeclaration'));
    }

    public function submit(IncomeChangeDeclaration $incomeChangeDeclaration): RedirectResponse
    {
        Gate::authorize('update', $incomeChangeDeclaration);
        $this->service->submit($incomeChangeDeclaration, $this->currentUser());

        return back()->with('success', 'Declaração submetida.');
    }
}
