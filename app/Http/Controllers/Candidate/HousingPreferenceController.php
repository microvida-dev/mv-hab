<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHousingPreferenceRequest;
use App\Http\Requests\UpdateHousingPreferenceRequest;
use App\Models\Application;
use App\Models\HousingPreference;
use App\Services\Allocation\HousingPreferenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class HousingPreferenceController extends Controller
{
    public function __construct(private readonly HousingPreferenceService $service) {}

    public function index(): View
    {
        return view('candidate.housing-preferences.index', [
            'applications' => Application::query()
                ->forUser($this->currentUser())
                ->readyForAllocation()
                ->with(['contest', 'housingPreferences.housingUnit'])
                ->latest()
                ->get(),
        ]);
    }

    public function edit(Application $application): View
    {
        Gate::authorize('update', [HousingPreference::class, $application]);

        return view('candidate.housing-preferences.edit', [
            'application' => $application->load(['contest', 'housingPreferences.housingUnit']),
            'availableUnits' => $this->service->availableFor($application),
        ]);
    }

    public function update(UpdateHousingPreferenceRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', [HousingPreference::class, $application]);
        $this->service->replace($application, $request->validated('preferences'), $this->authenticatedUser($request), false);

        return to_route('candidate.housing-preferences.edit', $application)->with('success', 'Preferências guardadas.');
    }

    public function submit(StoreHousingPreferenceRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', [HousingPreference::class, $application]);
        $this->service->replace($application, $request->validated('preferences'), $this->authenticatedUser($request), true);

        return to_route('candidate.housing-preferences.index')->with('success', 'Preferências submetidas.');
    }
}
