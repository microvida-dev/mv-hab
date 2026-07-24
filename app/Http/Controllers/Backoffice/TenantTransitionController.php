<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunTenantTransitionRequest;
use App\Models\TenantTransition;
use App\Models\WinnerRegistration;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\TenantTransition\TenantTransitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantTransitionController extends Controller
{
    public function __construct(
        private readonly TenantTransitionService $transitions,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', TenantTransition::class);
        $actor = $this->authenticatedUser($request);

        return view('backoffice.tenant-transitions.index', [
            'transitions' => $this->municipalScope
                ->tenantTransitions(TenantTransition::query(), $actor)
                ->with(['tenant', 'allocation', 'leaseContract'])
                ->latest()
                ->paginate(25),
            'winners' => $this->municipalScope
                ->winnerRegistrations(WinnerRegistration::query(), $actor)
                ->with(['candidate', 'allocation'])
                ->latest()
                ->get(),
        ]);
    }

    public function run(RunTenantTransitionRequest $request): RedirectResponse
    {
        Gate::authorize('runBackoffice', TenantTransition::class);

        /** @var WinnerRegistration $winner */
        $winner = $this->municipalScope
            ->winnerRegistrations(
                WinnerRegistration::query(),
                $this->authenticatedUser($request),
            )
            ->findOrFail((int) $request->validated('winner_registration_id'));
        $this->transitions->run($winner, $this->authenticatedUser($request));

        return to_route('backoffice.tenant-transitions.index')->with('success', 'Transição para inquilino processada.');
    }
}
