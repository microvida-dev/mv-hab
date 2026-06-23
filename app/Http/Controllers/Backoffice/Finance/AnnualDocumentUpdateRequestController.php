<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectFinanceRecordRequest;
use App\Http\Requests\ReviewIncomeChangeDeclarationRequest;
use App\Http\Requests\StoreAnnualDocumentUpdateRequestRequest;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\AnnualDocumentUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AnnualDocumentUpdateRequestController extends Controller
{
    public function __construct(private readonly AnnualDocumentUpdateService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', AnnualDocumentUpdateRequest::class);
        $requests = AnnualDocumentUpdateRequest::query()->with(['tenant', 'tenantFinancialAccount'])->latest()->paginate(25);

        return view('backoffice.finance.annual-document-updates.index', compact('requests'));
    }

    public function store(StoreAnnualDocumentUpdateRequestRequest $request): RedirectResponse
    {
        Gate::authorize('create', AnnualDocumentUpdateRequest::class);
        $account = TenantFinancialAccount::query()->findOrFail($request->integer('tenant_financial_account_id'));
        $documentRequest = $this->service->request($account, $this->authenticatedUser($request), $request->validated());

        return redirect()->route('backoffice.finance.annual-document-updates.show', $documentRequest)->with('success', 'Pedido documental anual criado.');
    }

    public function show(AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): View
    {
        Gate::authorize('view', $annualDocumentUpdateRequest);
        $annualDocumentUpdateRequest->load(['tenant', 'tenantFinancialAccount', 'submissions.documentSubmission']);

        return view('backoffice.finance.annual-document-updates.show', compact('annualDocumentUpdateRequest'));
    }

    public function accept(ReviewIncomeChangeDeclarationRequest $request, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): RedirectResponse
    {
        Gate::authorize('update', $annualDocumentUpdateRequest);
        $this->service->accept($annualDocumentUpdateRequest, $this->authenticatedUser($request), $request->validated('notes'));

        return back()->with('success', 'Pedido documental aceite.');
    }

    public function reject(RejectFinanceRecordRequest $request, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): RedirectResponse
    {
        Gate::authorize('update', $annualDocumentUpdateRequest);
        $this->service->reject($annualDocumentUpdateRequest, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Pedido documental rejeitado.');
    }
}
