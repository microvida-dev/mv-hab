<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunTenantTransitionRequest;
use App\Models\TenantTransition;
use App\Models\WinnerRegistration;
use App\Services\TenantTransition\TenantTransitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TenantTransitionController extends Controller
{
    public function __construct(private readonly TenantTransitionService $transitions) {}

    public function index(): View
    {
        Gate::authorize('viewAny', TenantTransition::class);

        return view('backoffice.tenant-transitions.index', [
            'transitions' => TenantTransition::query()->with(['tenant', 'allocation', 'leaseContract'])->latest()->paginate(25),
            'winners' => WinnerRegistration::query()->with(['candidate', 'allocation'])->latest()->get(),
        ]);
    }

    public function run(RunTenantTransitionRequest $request): RedirectResponse
    {
        Gate::authorize('create', TenantTransition::class);

        /** @var WinnerRegistration $winner */
        $winner = WinnerRegistration::query()->findOrFail((int) $request->validated('winner_registration_id'));
        $this->transitions->run($winner, $this->authenticatedUser($request));

        return to_route('backoffice.tenant-transitions.index')->with('success', 'Transição para inquilino processada.');
    }
}
