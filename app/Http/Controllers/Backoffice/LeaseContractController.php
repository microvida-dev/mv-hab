<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateLeaseContractRequest;
use App\Http\Requests\CancelLeaseContractRequest;
use App\Http\Requests\IssueLeaseContractRequest;
use App\Http\Requests\StoreLeaseContractRequest;
use App\Http\Requests\SuspendLeaseContractRequest;
use App\Http\Requests\TerminateLeaseContractRequest;
use App\Http\Requests\UpdateLeaseContractRequest;
use App\Models\Allocation;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\RentCalculation;
use App\Services\Contracts\ContractActivationService;
use App\Services\Contracts\LeaseContractService;
use App\Services\Contracts\LeaseContractStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LeaseContractController extends Controller
{
    public function __construct(
        private readonly LeaseContractService $service,
        private readonly ContractActivationService $activationService,
        private readonly LeaseContractStatusService $statusService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Contract::class);

        return view('backoffice.contracts.leases.index', [
            'contracts' => Contract::query()->processual()->with(['candidate', 'housingUnit', 'contest', 'deposit'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Contract::class);

        return view('backoffice.contracts.leases.create', [
            'allocations' => Allocation::query()->readyForContract()->doesntHave('leaseContract')->with(['candidate', 'housingUnit', 'application'])->get(),
            'calculations' => RentCalculation::query()->where('status', 'approved')->with(['allocation', 'candidate'])->latest()->get(),
            'templates' => ContractTemplate::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLeaseContractRequest $request): RedirectResponse
    {
        Gate::authorize('create', Contract::class);

        $validated = $request->validated();

        $allocation = Allocation::query()->findOrFail((int) $validated['allocation_id']);
        $calculation = RentCalculation::query()->findOrFail((int) $validated['rent_calculation_id']);
        $template = ContractTemplate::query()->findOrFail((int) $validated['contract_template_id']);

        $contract = $this->service->createFromAllocation(
            $allocation,
            $calculation,
            $template,
            $this->authenticatedUser($request),
            $validated,
        );

        return to_route('backoffice.contracts.leases.show', $contract)
            ->with('success', 'Contrato criado em preparação.');
    }

    public function show(Contract $leaseContract): View
    {
        Gate::authorize('view', $leaseContract);
        $leaseContract->load([
            'candidate',
            'application',
            'allocation',
            'housingUnit',
            'contest',
            'program',
            'rentCalculation.details',
            'contractTemplate',
            'parties',
            'clauses',
            'deposit',
            'generatedDocuments',
            'validations',
            'signatures',
            'statusHistories',
        ]);

        return view('backoffice.contracts.leases.show', compact('leaseContract'));
    }

    public function edit(Contract $leaseContract): View
    {
        Gate::authorize('update', $leaseContract);

        return view('backoffice.contracts.leases.edit', compact('leaseContract'));
    }

    public function update(UpdateLeaseContractRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('update', $leaseContract);
        $this->service->updatePreparation($leaseContract, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.contracts.leases.show', $leaseContract)->with('success', 'Contrato atualizado.');
    }

    public function issue(IssueLeaseContractRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('issue', $leaseContract);
        $this->service->issue($leaseContract, $this->authenticatedUser($request), $request->validated('issue_notes'));

        return back()->with('success', 'Contrato emitido.');
    }

    public function activate(ActivateLeaseContractRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('activate', $leaseContract);
        $this->activationService->activate($leaseContract, $this->authenticatedUser($request), $request->validated('activation_reason'));

        return back()->with('success', 'Contrato ativado.');
    }

    public function suspend(SuspendLeaseContractRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('update', $leaseContract);
        $this->statusService->transition($leaseContract, ContractStatus::Suspended, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Contrato suspenso.');
    }

    public function terminate(TerminateLeaseContractRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('update', $leaseContract);
        $this->statusService->transition($leaseContract, ContractStatus::Terminated, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Contrato terminado.');
    }

    public function cancel(CancelLeaseContractRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('update', $leaseContract);
        $this->service->cancel($leaseContract, $this->authenticatedUser($request), $request->validated('reason'));

        return back()->with('success', 'Contrato cancelado.');
    }
}
