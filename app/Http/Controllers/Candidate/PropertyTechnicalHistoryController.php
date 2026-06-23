<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\PropertyHistoryEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class PropertyTechnicalHistoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PropertyHistoryEvent::class);
        $events = PropertyHistoryEvent::query()
            ->where('visible_to_tenant', true)
            ->whereHas('leaseContract', fn ($query) => $query->where('user_id', $this->currentUser()->id))
            ->with('housingUnit')
            ->latest('occurred_at')
            ->paginate(20);

        return view('candidate.property.technical-history', compact('events'));
    }
}
