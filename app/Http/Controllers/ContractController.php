<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\HousingUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContractController extends Controller
{
    public function index(): View
    {
        $contracts = Contract::query()
            ->with(['citizen', 'housingUnit'])
            ->latest()
            ->paginate(15);

        return view('contracts.index', compact('contracts'));
    }

    public function create(): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $housingUnits = HousingUnit::query()
            ->orderBy('code')
            ->get(['id', 'code', 'address']);

        return view('contracts.create', compact('citizens', 'housingUnits'));
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $contract = new Contract;
        $contract->forceFill($request->validated())->save();

        return to_route('contracts.index')
            ->with('success', 'Contrato criado com sucesso.');
    }

    public function show(Contract $contract): View
    {
        $contract->load(['citizen', 'housingUnit', 'payments', 'documents']);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $housingUnits = HousingUnit::query()
            ->orderBy('code')
            ->get(['id', 'code', 'address']);

        return view('contracts.edit', compact('contract', 'citizens', 'housingUnits'));
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $contract->forceFill($request->validated())->save();

        return to_route('contracts.index')
            ->with('success', 'Contrato atualizado com sucesso.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return to_route('contracts.index')
            ->with('success', 'Contrato eliminado com sucesso.');
    }
}
