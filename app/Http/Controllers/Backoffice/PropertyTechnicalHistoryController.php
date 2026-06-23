<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\HousingUnit;
use App\Models\PropertyHistoryEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PropertyTechnicalHistoryController extends Controller
{
    public function show(HousingUnit $housingUnit): View
    {
        Gate::authorize('viewAny', PropertyHistoryEvent::class);
        $events = $housingUnit->propertyHistoryEvents()
            ->when(request('event_type'), fn ($query, $type) => $query->where('event_type', $type))
            ->when(request('from'), fn ($query, $from) => $query->whereDate('occurred_at', '>=', $from))
            ->when(request('to'), fn ($query, $to) => $query->whereDate('occurred_at', '<=', $to))
            ->paginate(30);

        return view('backoffice.properties.technical-history', compact('housingUnit', 'events'));
    }
}
