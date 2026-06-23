<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\SearchHousingOfferRequest;
use App\Services\PublicPortal\PublicContestService;
use App\Services\PublicPortal\PublicHousingMapService;
use App\Services\PublicPortal\PublicHousingSearchService;
use App\Services\PublicPortal\PublicPortalLinkService;
use App\Services\PublicPortal\PublicPortalSeoService;
use App\Services\PublicPortal\PublicPortalSettingsService;
use Illuminate\Contracts\View\View;

class HousingOfferController extends Controller
{
    public function index(
        SearchHousingOfferRequest $request,
        PublicHousingSearchService $searchService,
        PublicHousingMapService $mapService,
        PublicContestService $contestService,
        PublicPortalSettingsService $settingsService,
        PublicPortalLinkService $linkService,
        PublicPortalSeoService $seoService,
    ): View {
        $filters = $request->filters();
        $settings = $settingsService->all();

        return view('public.housing-offer.index', [
            'housingUnits' => $searchService->paginate($filters),
            'filterOptions' => $searchService->filterOptions(),
            'markers' => ($settings['show_map'] ?? true) ? $mapService->markers($filters) : [],
            'contests' => $contestService->paginate(['status' => 'open'], 6),
            'links' => $linkService->active(),
            'settings' => $settings,
            'seo' => $seoService->offerIndex(),
            'filters' => $filters,
        ]);
    }
}
