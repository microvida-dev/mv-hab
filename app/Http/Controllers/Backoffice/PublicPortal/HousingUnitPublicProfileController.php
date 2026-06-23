<?php

namespace App\Http\Controllers\Backoffice\PublicPortal;

use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitPublicDocumentType;
use App\Enums\PublicVisibilityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\PublicPortal\UpdateHousingUnitPublicProfileRequest;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\Municipality;
use App\Services\PublicPortal\PublicHousingPublicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HousingUnitPublicProfileController extends Controller
{
    public function edit(HousingUnit $housingUnit): View
    {
        Gate::authorize('updatePublicProfile', $housingUnit);

        $housingUnit->load(['images', 'publicDocumentRecords', 'contestHousingUnits.contest']);

        return view('backoffice.public-portal.housing-units.edit', [
            'housingUnit' => $housingUnit,
            'municipalities' => Municipality::query()->orderBy('name')->get(),
            'contests' => Contest::query()->orderByDesc('created_at')->get(),
            'visibilityStatuses' => PublicVisibilityStatus::options(),
            'publicStatuses' => HousingPublicStatus::options(),
            'locationPrecisions' => HousingLocationPrecision::options(),
            'documentTypes' => HousingUnitPublicDocumentType::options(),
        ]);
    }

    public function update(
        UpdateHousingUnitPublicProfileRequest $request,
        HousingUnit $housingUnit,
        PublicHousingPublicationService $publicationService,
    ): RedirectResponse {
        Gate::authorize('updatePublicProfile', $housingUnit);

        $publicationService->updateProfile($housingUnit, $request->profileData(), $this->authenticatedUser($request));

        return to_route('backoffice.public-portal.housing-units.edit', $housingUnit)
            ->with('success', 'Ficha pública atualizada.');
    }

    public function publish(Request $request, HousingUnit $housingUnit, PublicHousingPublicationService $publicationService): RedirectResponse
    {
        Gate::authorize('publishPublicProfile', $housingUnit);

        $publicationService->publish($housingUnit, $this->authenticatedUser($request));

        return back()->with('success', 'Habitação publicada no portal.');
    }

    public function unpublish(Request $request, HousingUnit $housingUnit, PublicHousingPublicationService $publicationService): RedirectResponse
    {
        Gate::authorize('publishPublicProfile', $housingUnit);

        $publicationService->unpublish($housingUnit, $this->authenticatedUser($request));

        return back()->with('success', 'Habitação retirada do portal.');
    }

    public function preview(HousingUnit $housingUnit): View
    {
        Gate::authorize('previewPublicProfile', $housingUnit);

        $housingUnit->load(['publicFeatures', 'publicImages', 'publicDocuments', 'contestHousingUnits.contest']);

        return view('public.housing-units.show', [
            'housingUnit' => $housingUnit,
            'seo' => ['title' => $housingUnit->displayTitle(), 'description' => $housingUnit->public_summary ?? 'Pré-visualização pública'],
            'jsonLd' => [],
        ]);
    }
}
