<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectFinanceRecordRequest;
use App\Http\Requests\ReviewIncomeChangeDeclarationRequest;
use App\Models\IncomeChangeDeclaration;
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
        $declarations = IncomeChangeDeclaration::query()->with(['tenant', 'tenantFinancialAccount', 'rentReview'])->latest()->paginate(25);

        return view('backoffice.finance.income-changes.index', compact('declarations'));
    }

    public function show(IncomeChangeDeclaration $incomeChangeDeclaration): View
    {
        Gate::authorize('view', $incomeChangeDeclaration);
        $incomeChangeDeclaration->load(['tenant', 'tenantFinancialAccount', 'rentReview']);

        return view('backoffice.finance.income-changes.show', compact('incomeChangeDeclaration'));
    }

    public function accept(ReviewIncomeChangeDeclarationRequest $request, IncomeChangeDeclaration $incomeChangeDeclaration): RedirectResponse
    {
        Gate::authorize('update', $incomeChangeDeclaration);
        $this->service->accept($incomeChangeDeclaration, $this->authenticatedUser($request), $request->validated('notes'));

        return back()->with('success', 'Declaração aceite e revisão de renda criada.');
    }

    public function reject(RejectFinanceRecordRequest $request, IncomeChangeDeclaration $incomeChangeDeclaration): RedirectResponse
    {
        Gate::authorize('update', $incomeChangeDeclaration);
        $this->service->reject($incomeChangeDeclaration, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Declaração rejeitada.');
    }
}
