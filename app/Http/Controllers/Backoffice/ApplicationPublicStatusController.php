<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ProcessTracking\ApplicationPublicStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationPublicStatusController extends Controller
{
    public function __construct(private readonly ApplicationPublicStatusService $statuses) {}

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);

        return view('backoffice.processes.public-status', [
            'application' => $application,
            'snapshot' => $this->statuses->refresh($application),
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $this->statuses->refresh($application);

        return back()->with('success', 'Estado público recalculado.');
    }
}
