<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\SearchHousingOfferRequest;
use App\Services\PublicPortal\PublicHousingSearchService;
use App\Services\PublicPortal\PublicHousingUnitService;
use App\Services\PublicPortal\PublicPortalSeoService;
use App\Services\Visits\PublicVisitBookingService;
use App\Services\Visits\PublicVisitChallengeService;
use Illuminate\Contracts\View\View;

class PublicHousingUnitController extends Controller
{
    public function index(SearchHousingOfferRequest $request, PublicHousingSearchService $searchService): View
    {
        return view('public.housing-units.index', [
            'housingUnits' => $searchService->paginate($request->filters()),
            'filterOptions' => $searchService->filterOptions(),
            'filters' => $request->filters(),
        ]);
    }

    public function show(
        string $slug,
        PublicHousingUnitService $housingUnitService,
        PublicPortalSeoService $seoService,
        PublicVisitBookingService $publicVisits,
        PublicVisitChallengeService $challenge,
    ): View {
        $housingUnit = $housingUnitService->findBySlug($slug);

        return view('public.housing-units.show', [
            'housingUnit' => $housingUnit,
            'seo' => $seoService->housingUnit($housingUnit),
            'jsonLd' => $seoService->housingUnitJsonLd($housingUnit),
            'ogImage' => $seoService->housingUnitImageUrl($housingUnit),
            'publicVisitSlots' => $publicVisits->slotsFor($housingUnit),
            'publicVisitTurnstileEnabled' => $challenge->enabled(),
            'publicVisitTurnstileSiteKey' => $challenge->siteKey(),
            'publicVisitTurnstileAction' => $challenge->action(),
        ]);
    }

    public function brochure(string $slug, PublicHousingUnitService $housingUnitService, PublicPortalSeoService $seoService): View
    {
        $housingUnit = $housingUnitService->findBySlug($slug);

        return view('public.housing-units.brochure', [
            'housingUnit' => $housingUnit,
            'contestHousingUnit' => $housingUnit->contestHousingUnits->first(),
            'seo' => $seoService->housingUnit($housingUnit),
            'ogImage' => $seoService->housingUnitImageUrl($housingUnit),
        ]);
    }
}
