<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\SearchPublicHousingMapRequest;
use App\Services\PublicPortal\PublicHousingMapService;
use App\Services\PublicPortal\PublicPortalSettingsService;
use Illuminate\Http\JsonResponse;

class PublicHousingMapController extends Controller
{
    public function index(
        SearchPublicHousingMapRequest $request,
        PublicHousingMapService $mapService,
        PublicPortalSettingsService $settingsService,
    ): JsonResponse {
        $settings = $settingsService->all();
        $enabled = (bool) ($settings['show_map'] ?? true);

        return response()->json([
            'enabled' => $enabled,
            'center' => [
                'latitude' => (float) ($settings['map_center_lat'] ?? 39.4595),
                'longitude' => (float) ($settings['map_center_lng'] ?? -8.6674),
                'zoom' => (int) ($settings['map_zoom'] ?? 12),
            ],
            'markers' => $enabled ? $mapService->markers($request->filters()) : [],
        ]);
    }
}
