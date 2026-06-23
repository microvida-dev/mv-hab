<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveInternalAlertRequest;
use App\Models\InternalAlert;
use App\Services\InternalAlerts\InternalAlertDetector;
use App\Services\InternalAlerts\InternalAlertResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InternalAlertController extends Controller
{
    public function __construct(
        private readonly InternalAlertDetector $detector,
        private readonly InternalAlertResolver $resolver,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', InternalAlert::class);
        $alerts = InternalAlert::query()->latest()->paginate(20);

        return view('backoffice.internal-alerts.index', compact('alerts'));
    }

    public function show(InternalAlert $internalAlert): View
    {
        Gate::authorize('view', $internalAlert);

        return view('backoffice.internal-alerts.show', compact('internalAlert'));
    }

    public function detect(): RedirectResponse
    {
        Gate::authorize('viewAny', InternalAlert::class);
        $count = $this->detector->detect($this->currentUser());

        return to_route('backoffice.internal-alerts.index')->with('success', $count.' alertas analisados/criados.');
    }

    public function resolve(ResolveInternalAlertRequest $request, InternalAlert $internalAlert): RedirectResponse
    {
        Gate::authorize('update', $internalAlert);
        $this->resolver->resolve($internalAlert, $this->authenticatedUser($request));

        return back()->with('success', 'Alerta resolvido.');
    }

    public function dismiss(ResolveInternalAlertRequest $request, InternalAlert $internalAlert): RedirectResponse
    {
        Gate::authorize('update', $internalAlert);
        $this->resolver->dismiss($internalAlert, $this->authenticatedUser($request));

        return back()->with('success', 'Alerta dispensado.');
    }
}
