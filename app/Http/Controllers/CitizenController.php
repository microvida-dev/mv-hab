<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCitizenRequest;
use App\Http\Requests\UpdateCitizenRequest;
use App\Models\Citizen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CitizenController extends Controller
{
    public function index(): View
    {
        $citizens = Citizen::query()
            ->withCount(['households', 'housingApplications', 'contracts'])
            ->latest()
            ->paginate(15);

        return view('citizens.index', compact('citizens'));
    }

    public function create(): View
    {
        return view('citizens.create');
    }

    public function store(StoreCitizenRequest $request): RedirectResponse
    {
        Citizen::create($request->validated());

        return to_route('citizens.index')
            ->with('success', 'Munícipe criado com sucesso.');
    }

    public function show(Citizen $citizen): View
    {
        $citizen->load([
            'households',
            'housingApplications.household',
            'contracts.housingUnit',
            'maintenanceRequests.housingUnit',
            'documents',
        ]);

        return view('citizens.show', compact('citizen'));
    }

    public function edit(Citizen $citizen): View
    {
        return view('citizens.edit', compact('citizen'));
    }

    public function update(UpdateCitizenRequest $request, Citizen $citizen): RedirectResponse
    {
        $citizen->update($request->validated());

        return to_route('citizens.index')
            ->with('success', 'Munícipe atualizado com sucesso.');
    }

    public function destroy(Citizen $citizen): RedirectResponse
    {
        $citizen->delete();

        return to_route('citizens.index')
            ->with('success', 'Munícipe eliminado com sucesso.');
    }
}
