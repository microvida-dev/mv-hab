<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectFinanceRecordRequest;
use App\Http\Requests\ReviewIncomeChangeDeclarationRequest;
use App\Http\Requests\StoreAnnualDocumentUpdateRequestRequest;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\AnnualDocumentUpdateService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AnnualDocumentUpdateRequestController extends Controller
{
    public function __construct(
        private readonly AnnualDocumentUpdateService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', AnnualDocumentUpdateRequest::class);
        $requests = $this->municipalScope
            ->annualDocumentUpdateRequests(
                AnnualDocumentUpdateRequest::query(),
                $this->currentUser(),
            )
            ->with(['tenant', 'tenantFinancialAccount'])
            ->latest()
            ->paginate(25);

        return view('backoffice.finance.annual-document-updates.index', compact('requests'));
    }

    public function store(StoreAnnualDocumentUpdateRequestRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', AnnualDocumentUpdateRequest::class);
        $account = TenantFinancialAccount::query()->findOrFail($request->integer('tenant_financial_account_id'));
        abort_unless(
            $this->municipalScope->ownsTenantFinancialAccount(
                $this->authenticatedUser($request),
                $account,
            ),
            404,
        );
        $documentRequest = $this->service->request($account, $this->authenticatedUser($request), $request->validated());

        return redirect()->route('backoffice.finance.annual-document-updates.show', $documentRequest)->with('success', 'Pedido documental anual criado.');
    }

    public function show(AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): View
    {
        Gate::authorize('viewBackoffice', $annualDocumentUpdateRequest);
        $annualDocumentUpdateRequest->load(['tenant', 'tenantFinancialAccount', 'submissions.documentSubmission']);

        return view('backoffice.finance.annual-document-updates.show', compact('annualDocumentUpdateRequest'));
    }

    public function accept(ReviewIncomeChangeDeclarationRequest $request, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $annualDocumentUpdateRequest);
        $this->service->accept($annualDocumentUpdateRequest, $this->authenticatedUser($request), $request->validated('notes'));

        return back()->with('success', 'Pedido documental aceite.');
    }

    public function reject(RejectFinanceRecordRequest $request, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): RedirectResponse
    {
        Gate::authorize('rejectBackoffice', $annualDocumentUpdateRequest);
        $this->service->reject($annualDocumentUpdateRequest, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Pedido documental rejeitado.');
    }
}
