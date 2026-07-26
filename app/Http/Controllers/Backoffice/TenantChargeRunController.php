<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ChargeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RunTenantChargeRunRequest;
use App\Models\Contract;
use App\Models\TenantChargeRun;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\TenantBilling\TenantChargeRunService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantChargeRunController extends Controller
{
    public function __construct(
        private readonly TenantChargeRunService $chargeRuns,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', TenantChargeRun::class);
        $actor = $this->authenticatedUser($request);
        $contractIds = $this->municipalScope
            ->contracts(Contract::query(), $actor)
            ->select('id');

        $chargeRuns = $this->municipalScope
            ->tenantChargeRuns(TenantChargeRun::query(), $actor)
            ->withCount([
                'items' => fn ($items) => $items
                    ->whereIn('lease_contract_id', clone $contractIds),
            ])
            ->latest()
            ->paginate(20);

        return view('backoffice.tenant-charge-runs.index', compact('chargeRuns'));
    }

    public function show(Request $request, TenantChargeRun $tenantChargeRun): View
    {
        Gate::authorize('viewBackoffice', $tenantChargeRun);
        $contractIds = $this->municipalScope
            ->contracts(Contract::query(), $this->authenticatedUser($request))
            ->select('id');
        $tenantChargeRun->setRelation(
            'items',
            $tenantChargeRun->items()
                ->whereIn('lease_contract_id', $contractIds)
                ->with(['invoice', 'leaseContract.housingUnit', 'tenant'])
                ->get(),
        );

        return view('backoffice.tenant-charge-runs.show', compact('tenantChargeRun'));
    }

    public function store(RunTenantChargeRunRequest $request): RedirectResponse
    {
        Gate::authorize('runBackoffice', TenantChargeRun::class);
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
