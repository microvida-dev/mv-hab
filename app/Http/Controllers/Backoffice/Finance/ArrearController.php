<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFinanceRecordRequest;
use App\Models\Arrear;
use App\Models\TenantFinancialAccount;
use App\Services\Finance\ArrearDetectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ArrearController extends Controller
{
    public function __construct(private readonly ArrearDetectionService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Arrear::class);
        $arrears = Arrear::query()->with(['tenant', 'tenantFinancialAccount', 'rentInstallment'])->latest()->paginate(25);

        return view('backoffice.finance.arrears.index', compact('arrears'));
    }

    public function show(Arrear $arrear): View
    {
        Gate::authorize('view', $arrear);
        $arrear->load(['tenant', 'tenantFinancialAccount', 'rentInstallment', 'defaultNotices', 'regularizationAgreement']);

        return view('backoffice.finance.arrears.show', compact('arrear'));
    }

    public function detect(TenantFinancialAccount $tenantFinancialAccount): RedirectResponse
    {
        Gate::authorize('update', $tenantFinancialAccount);
        $created = $this->service->detectForAccount($tenantFinancialAccount, $this->currentUser());

        return back()->with('success', "Deteção de incumprimentos concluída. Novos registos: {$created}.");
    }

    public function close(CancelFinanceRecordRequest $request, Arrear $arrear): RedirectResponse
    {
        Gate::authorize('update', $arrear);
        $this->service->close($arrear, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Incumprimento fechado.');
    }
}
