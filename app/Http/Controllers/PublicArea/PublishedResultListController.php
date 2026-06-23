<?php

namespace App\Http\Controllers\PublicArea;

use App\Enums\ListPublicationType;
use App\Http\Controllers\Controller;
use App\Models\DefinitiveList;
use App\Models\ListPublication;
use App\Models\ProvisionalList;
use App\Services\Lists\ListAnonymizationService;
use Illuminate\Contracts\View\View;

class PublishedResultListController extends Controller
{
    public function __construct(private readonly ListAnonymizationService $anonymizationService) {}

    public function index(): View
    {
        $publications = ListPublication::query()
            ->publicPortal()
            ->visible()
            ->with('publishable')
            ->latest('published_at')
            ->paginate(10);

        return view('public.results.index', compact('publications'));
    }

    public function show(ListPublication $listPublication): View
    {
        abort_unless(
            ListPublication::query()
                ->whereKey($listPublication->getKey())
                ->publicPortal()
                ->visible()
                ->exists(),
            404
        );

        $listPublication->load('publishable.entries');
        $publishable = $listPublication->publishable;
        abort_unless($publishable instanceof ProvisionalList || $publishable instanceof DefinitiveList, 404);

        $entries = $publishable->entries
            ->map(function ($entry) use ($listPublication) {
                return $this->anonymizationService->publicPayload($entry, $listPublication->anonymization_mode);
            });

        return view('public.results.show', [
            'publication' => $listPublication,
            'entries' => $entries,
            'isDefinitive' => $listPublication->publication_type === ListPublicationType::DefinitiveList->value,
        ]);
    }
}
