<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
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
        Gate::authorize('viewAny', HousingPreference::class);

        return view('candidate.housing-preferences.index', [
            'applications' => Application::query()
                ->forUser($this->currentUser())
                ->where('status', ApplicationStatus::Draft->value)
                ->with(['contest', 'housingPreferences.housingUnit'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function edit(Application $application): View
    {
        Gate::authorize('update', [HousingPreference::class, $application]);
        $compatibleOptions = $this->service->optionsFor($application);

        return view('candidate.housing-preferences.edit', [
            'application' => $application->load(['contest', 'housingPreferences.housingUnit']),
            'compatibleOptions' => $compatibleOptions,
            'compatibilitySummary' => $this->service
                ->compatibilitySummary($application),
            'selectionConfiguration' => $this->service->selectionConfiguration(
                $application,
                $compatibleOptions,
            ),
            'preferenceReadiness' => $this->service->readinessForSubmission($application),
        ]);
    }

    public function update(UpdateHousingPreferenceRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', [HousingPreference::class, $application]);
        $this->service->replace($application, $request->validated('preferences'), $this->authenticatedUser($request), false);

        return to_route('candidate.housing-preferences.edit', $application)->with('success', 'Ordem dos fogos guardada.');
    }

    public function submit(StoreHousingPreferenceRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', [HousingPreference::class, $application]);
        $this->service->replace($application, $request->validated('preferences'), $this->authenticatedUser($request), true);

        return to_route('candidate.housing-preferences.index')->with('success', 'Ordem dos fogos confirmada.');
    }
}
