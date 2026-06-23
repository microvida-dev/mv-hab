<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterLandlordDashboardRequest;
use App\Models\LandlordDashboardSnapshot;
use App\Services\LandlordOperations\LandlordDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class LandlordDashboardController extends Controller
{
    public function __construct(private readonly LandlordDashboardService $dashboard) {}

    public function __invoke(FilterLandlordDashboardRequest $request): View
    {
        Gate::authorize('viewAny', LandlordDashboardSnapshot::class);

        return view('backoffice.landlord.dashboard', [
            'metrics' => $this->dashboard->metrics(),
            'snapshot' => $this->dashboard->snapshot($this->authenticatedUser($request)),
        ]);
    }
}
