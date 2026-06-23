<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAdhesionRegistrationRequest;
use App\Http\Requests\FinalizeAdhesionRegistrationRequest;
use App\Http\Requests\RemoveAdhesionRegistrationRequest;
use App\Http\Requests\StoreAdhesionRegistrationRequest;
use App\Http\Requests\UpdateAdhesionRegistrationRequest;
use App\Models\AdhesionRegistration;
use App\Services\Candidate\AdhesionRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdhesionRegistrationController extends Controller
{
    public function __construct(private readonly AdhesionRegistrationService $registrationService) {}

    public function show(Request $request): View|RedirectResponse
    {
        $registration = $this->registrationFor($request);

        if ($registration === null) {
            return to_route('candidate.registration.create');
        }

        Gate::authorize('view', $registration);
        $registration->load('statusHistories.changedBy');

        return view('candidate.registration.show', compact('registration'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->registrationFor($request) !== null) {
            return to_route('candidate.registration.show');
        }

        Gate::authorize('create', AdhesionRegistration::class);

        return view('candidate.registration.create');
    }

    public function store(StoreAdhesionRegistrationRequest $request): RedirectResponse
    {
        $this->registrationService->create($request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.registration.show')
            ->with('success', 'Registo de Adesão iniciado e guardado como rascunho.');
    }

    public function edit(Request $request): View
    {
        $registration = $this->registrationFor($request);
        abort_if($registration === null, 404);

        Gate::authorize('update', $registration);

        return view('candidate.registration.edit', compact('registration'));
    }

    public function update(UpdateAdhesionRegistrationRequest $request): RedirectResponse
    {
        $registration = $this->registrationFor($request);
        abort_if($registration === null, 404);

        $this->registrationService->update($registration, $request->validated(), $this->authenticatedUser($request));

        return to_route('candidate.registration.show')
            ->with('success', 'Rascunho atualizado com sucesso.');
    }

    public function finalize(FinalizeAdhesionRegistrationRequest $request): RedirectResponse
    {
        $registration = $this->registrationFor($request);
        abort_if($registration === null, 404);

        $this->registrationService->finalize($registration, $this->authenticatedUser($request));

        return to_route('candidate.registration.show')
            ->with('success', 'O Registo de Adesão foi finalizado com sucesso.');
    }

    public function cancel(CancelAdhesionRegistrationRequest $request): RedirectResponse
    {
        $registration = $this->registrationFor($request);
        abort_if($registration === null, 404);

        $this->registrationService->cancel(
            $registration,
            $this->authenticatedUser($request),
            $request->validated('reason'),
        );

        return to_route('candidate.registration.show')
            ->with('success', 'O Registo de Adesão foi cancelado.');
    }

    public function remove(RemoveAdhesionRegistrationRequest $request): RedirectResponse
    {
        $registration = $this->registrationFor($request);
        abort_if($registration === null, 404);

        $this->registrationService->remove(
            $registration,
            $this->authenticatedUser($request),
            $request->validated('reason'),
        );

        return to_route('candidate.dashboard')
            ->with('success', 'O Registo de Adesão foi removido da área ativa.');
    }

    private function registrationFor(Request $request): ?AdhesionRegistration
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->first();

        return $registration instanceof AdhesionRegistration ? $registration : null;
    }
}
