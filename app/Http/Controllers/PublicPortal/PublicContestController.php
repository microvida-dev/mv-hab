<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\SearchPublicContestRequest;
use App\Services\PublicPortal\PublicContestService;
use App\Services\PublicPortal\PublicPortalSeoService;
use Illuminate\Contracts\View\View;

class PublicContestController extends Controller
{
    public function index(SearchPublicContestRequest $request, PublicContestService $contestService): View
    {
        return view('public.contests.index', [
            'contests' => $contestService->paginate($request->filters()),
            'filters' => $request->filters(),
            'statuses' => [
                'open' => 'Candidaturas abertas',
                'upcoming' => 'Abertura futura',
                'closed' => 'Prazo encerrado',
            ],
        ]);
    }

    public function show(string $slug, PublicContestService $contestService, PublicPortalSeoService $seoService): View
    {
        $contest = $contestService->findBySlug($slug);
        $housingUnits = $contest->contestHousingUnits
            ->pluck('housingUnit')
            ->filter()
            ->values();

        return view('public.contests.show', [
            'contest' => $contest,
            'housingUnits' => $housingUnits,
            'seo' => $seoService->contest($contest),
            'jsonLd' => $seoService->contestJsonLd($contest),
        ]);
    }
}
