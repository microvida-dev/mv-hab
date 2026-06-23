<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ChargeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RunTenantChargeRunRequest;
use App\Models\TenantChargeRun;
use App\Services\TenantBilling\TenantChargeRunService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TenantChargeRunController extends Controller
{
    public function __construct(private readonly TenantChargeRunService $chargeRuns) {}

    public function index(): View
    {
        Gate::authorize('viewAny', TenantChargeRun::class);

        $chargeRuns = TenantChargeRun::query()->withCount('items')->latest()->paginate(20);

        return view('backoffice.tenant-charge-runs.index', compact('chargeRuns'));
    }

    public function show(TenantChargeRun $tenantChargeRun): View
    {
        Gate::authorize('view', $tenantChargeRun);
        $tenantChargeRun->load(['items.invoice', 'items.leaseContract.housingUnit', 'items.tenant']);

        return view('backoffice.tenant-charge-runs.show', compact('tenantChargeRun'));
    }

    public function store(RunTenantChargeRunRequest $request): RedirectResponse
    {
        Gate::authorize('create', TenantChargeRun::class);
        $data = $request->validated();
        $run = $this->chargeRuns->run(
            $this->authenticatedUser($request),
            (int) $data['period_year'],
            (int) $data['period_month'],
            ChargeType::from($data['charge_type']),
        );

        return to_route('backoffice.tenant-operations.charge-runs.show', $run)->with('success', 'Execução de cobranças concluída.');
    }
}
