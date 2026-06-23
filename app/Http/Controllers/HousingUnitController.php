<?php

namespace App\Http\Controllers;

use App\Enums\HousingUnitStatus;
use App\Http\Requests\StoreHousingUnitRequest;
use App\Http\Requests\UpdateHousingUnitRequest;
use App\Models\HousingUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HousingUnitController extends Controller
{
    public function index(): View
    {
        $housingUnits = HousingUnit::query()
            ->withCount(['contracts', 'maintenanceRequests'])
            ->latest()
            ->paginate(15);

        return view('housing-units.index', compact('housingUnits'));
    }

    public function create(): View
    {
        $statuses = HousingUnitStatus::options();

        return view('housing-units.create', compact('statuses'));
    }

    public function store(StoreHousingUnitRequest $request): RedirectResponse
    {
        HousingUnit::create($request->validated());

        return to_route('housing-units.index')
            ->with('success', 'Habitação criada com sucesso.');
    }

    public function show(HousingUnit $housingUnit): View
    {
        $housingUnit->load(['contracts.citizen', 'maintenanceRequests.citizen']);

        return view('housing-units.show', compact('housingUnit'));
    }

    public function edit(HousingUnit $housingUnit): View
    {
        $statuses = HousingUnitStatus::options();

        return view('housing-units.edit', compact('housingUnit', 'statuses'));
    }

    public function update(UpdateHousingUnitRequest $request, HousingUnit $housingUnit): RedirectResponse
    {
        $housingUnit->update($request->validated());

        return to_route('housing-units.index')
            ->with('success', 'Habitação atualizada com sucesso.');
    }

    public function destroy(HousingUnit $housingUnit): RedirectResponse
    {
        $housingUnit->delete();

        return to_route('housing-units.index')
            ->with('success', 'Habitação eliminada com sucesso.');
    }
}
