<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', Contract::class);
        $contracts = $this->municipalScope
            ->contracts(Contract::query(), $this->authenticatedUser($request))
            ->with(['citizen', 'housingUnit'])
            ->latest()
            ->paginate(15);

        return view('contracts.index', compact('contracts'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', Contract::class);
        $actor = $this->authenticatedUser($request);
        $citizens = $this->municipalScope
            ->citizens(Citizen::query(), $actor)
            ->orderBy('name')
            ->get(['id', 'name']);
        $housingUnits = $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->orderBy('code')
            ->get(['id', 'code', 'address']);

        return view('contracts.create', compact('citizens', 'housingUnits'));
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', Contract::class);
        $actor = $this->authenticatedUser($request);
        $data = $request->validated();

        $this->municipalScope
            ->citizens(Citizen::query(), $actor)
            ->findOrFail((int) $data['citizen_id']);
        $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->findOrFail((int) $data['housing_unit_id']);

        $contract = DB::transaction(function () use ($actor, $data): Contract {
            $contract = new Contract;
            $contract->forceFill(array_merge($data, [
                'status' => ContractStatus::Preparation,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]))->save();

            $this->audit->record(
                AuditEvents::CREATE,
                $contract,
                'contracts',
                'contract_create',
                'Contrato criado em preparação.',
                metadata: ['municipality_id' => $actor->municipality_id],
            );

            return $contract;
        });

        return to_route('contracts.index')
            ->with('success', 'Contrato criado com sucesso.');
    }

    public function show(Contract $contract): View
    {
        Gate::authorize('viewBackoffice', $contract);
        $contract->load(['citizen', 'housingUnit', 'payments', 'documents']);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Request $request, Contract $contract): View
    {
        Gate::authorize('updateBackoffice', $contract);
        $actor = $this->authenticatedUser($request);
        $citizens = $this->municipalScope
            ->citizens(Citizen::query(), $actor)
            ->orderBy('name')
            ->get(['id', 'name']);
        $housingUnits = $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->orderBy('code')
            ->get(['id', 'code', 'address']);

        return view('contracts.edit', compact('contract', 'citizens', 'housingUnits'));
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $contract);

        if ($contract->status !== ContractStatus::Preparation) {
            throw ValidationException::withMessages([
                'contract' => 'Só contratos em preparação podem ser alterados.',
            ]);
        }

        $actor = $this->authenticatedUser($request);
        $data = $request->validated();
        $this->municipalScope
            ->citizens(Citizen::query(), $actor)
            ->findOrFail((int) $data['citizen_id']);
        $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->findOrFail((int) $data['housing_unit_id']);

        DB::transaction(function () use ($actor, $contract, $data): void {
            $contract->forceFill(array_merge($data, ['updated_by' => $actor->id]))->save();
            $this->audit->record(
                AuditEvents::UPDATE,
                $contract,
                'contracts',
                'contract_update',
                'Contrato em preparação atualizado.',
                metadata: ['municipality_id' => $actor->municipality_id],
            );
        });

        return to_route('contracts.index')
            ->with('success', 'Contrato atualizado com sucesso.');
    }

    public function destroy(Request $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $contract);

        if ($contract->status !== ContractStatus::Preparation) {
            throw ValidationException::withMessages([
                'contract' => 'Só contratos em preparação podem ser eliminados.',
            ]);
        }

        $actor = $this->authenticatedUser($request);
        DB::transaction(function () use ($actor, $contract): void {
            $this->audit->record(
                AuditEvents::DELETE,
                $contract,
                'contracts',
                'contract_delete',
                'Contrato em preparação arquivado.',
                metadata: ['municipality_id' => $actor->municipality_id],
            );
            $contract->delete();
        });

        return to_route('contracts.index')
            ->with('success', 'Contrato eliminado com sucesso.');
    }
}
