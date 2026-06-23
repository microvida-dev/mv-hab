<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Models\Citizen;
use App\Models\Household;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HouseholdController extends Controller
{
    public function index(): View
    {
        $households = Household::query()
            ->with(['citizen', 'adhesionRegistration'])
            ->withCount('housingApplications')
            ->latest()
            ->paginate(15);

        return view('households.index', compact('households'));
    }

    public function create(): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('households.create', compact('citizens'));
    }

    public function store(StoreHouseholdRequest $request): RedirectResponse
    {
        Household::create($request->validated());

        return to_route('households.index')
            ->with('success', 'Agregado familiar criado com sucesso.');
    }

    public function show(Household $household): View
    {
        $household->load(['citizen', 'adhesionRegistration', 'housingApplications.citizen']);

        return view('households.show', compact('household'));
    }

    public function edit(Household $household): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('households.edit', compact('household', 'citizens'));
    }

    public function update(UpdateHouseholdRequest $request, Household $household): RedirectResponse
    {
        $household->update($request->validated());

        return to_route('households.index')
            ->with('success', 'Agregado familiar atualizado com sucesso.');
    }

    public function destroy(Household $household): RedirectResponse
    {
        $household->delete();

        return to_route('households.index')
            ->with('success', 'Agregado familiar eliminado com sucesso.');
    }
}
