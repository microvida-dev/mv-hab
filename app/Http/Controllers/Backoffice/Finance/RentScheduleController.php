<?php

namespace App\Http\Controllers\Backoffice\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateRentScheduleRequest;
use App\Models\Contract;
use App\Models\RentSchedule;
use App\Services\Finance\RentScheduleService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RentScheduleController extends Controller
{
    public function __construct(
        private readonly RentScheduleService $service,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', RentSchedule::class);
        $schedules = $this->municipalScope
            ->rentSchedules(RentSchedule::query(), $this->currentUser())
            ->with(['tenant', 'leaseContract'])
            ->latest()
            ->paginate(20);

        return view('backoffice.finance.schedules.index', compact('schedules'));
    }

    public function show(RentSchedule $rentSchedule): View
    {
        Gate::authorize('viewBackoffice', $rentSchedule);
        $rentSchedule->load(['tenantFinancialAccount', 'tenant', 'leaseContract', 'installments']);

        return view('backoffice.finance.schedules.show', compact('rentSchedule'));
    }

    public function generate(GenerateRentScheduleRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('generateBackoffice', [RentSchedule::class, $leaseContract]);
        $schedule = $this->service->generateForContract($leaseContract, $this->authenticatedUser($request), $request->validated());

        return redirect()->route('backoffice.finance.schedules.show', $schedule)->with('success', 'Plano de rendas gerado.');
    }
}
