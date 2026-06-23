<?php

namespace App\Http\Controllers;

use App\Enums\HousingApplicationStatus;
use App\Http\Requests\StoreHousingApplicationRequest;
use App\Http\Requests\UpdateHousingApplicationRequest;
use App\Models\Citizen;
use App\Models\Household;
use App\Models\HousingApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HousingApplicationController extends Controller
{
    public function index(): View
    {
        $applications = HousingApplication::query()
            ->with(['citizen', 'household'])
            ->latest()
            ->paginate(15);

        return view('applications.index', compact('applications'));
    }

    public function create(): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $households = Household::query()
            ->with('citizen:id,name')
            ->orderBy('name')
            ->get(['id', 'citizen_id', 'name']);
        $statuses = HousingApplicationStatus::options();

        return view('applications.create', compact('citizens', 'households', 'statuses'));
    }

    public function store(StoreHousingApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === HousingApplicationStatus::Submitted->value && empty($validated['submitted_at'])) {
            $validated['submitted_at'] = now();
        }

        HousingApplication::create($validated);

        return to_route('applications.index')
            ->with('success', 'Candidatura criada com sucesso.');
    }

    public function show(HousingApplication $application): View
    {
        $application->load(['citizen', 'household', 'documents']);

        return view('applications.show', compact('application'));
    }

    public function edit(HousingApplication $application): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $households = Household::query()
            ->with('citizen:id,name')
            ->orderBy('name')
            ->get(['id', 'citizen_id', 'name']);
        $statuses = HousingApplicationStatus::options();

        return view('applications.edit', compact('application', 'citizens', 'households', 'statuses'));
    }

    public function update(UpdateHousingApplicationRequest $request, HousingApplication $application): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['status'] ?? null) === HousingApplicationStatus::Submitted->value && empty($validated['submitted_at'])) {
            $validated['submitted_at'] = $application->submitted_at ?? now();
        }

        $application->update($validated);

        return to_route('applications.index')
            ->with('success', 'Candidatura atualizada com sucesso.');
    }

    public function destroy(HousingApplication $application): RedirectResponse
    {
        $application->delete();

        return to_route('applications.index')
            ->with('success', 'Candidatura eliminada com sucesso.');
    }
}
