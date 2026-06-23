<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulator\StoreRegistrationRenewalRequest;
use App\Http\Requests\Simulator\SubmitRegistrationRenewalRequest;
use App\Http\Requests\Simulator\UpdateRegistrationRenewalRequest;
use App\Models\RegistrationRenewal;
use App\Services\Simulator\RegistrationRenewalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RegistrationRenewalController extends Controller
{
    public function __construct(private readonly RegistrationRenewalService $renewalService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', RegistrationRenewal::class);

        $renewals = RegistrationRenewal::query()
            ->where('user_id', $this->authenticatedUser($request)->id)
            ->latest()
            ->paginate(10);

        return view('candidate.registration-renewals.index', compact('renewals'));
    }

    public function create(): View
    {
        Gate::authorize('create', RegistrationRenewal::class);

        return view('candidate.registration-renewals.create');
    }

    public function store(StoreRegistrationRenewalRequest $request): RedirectResponse
    {
        Gate::authorize('create', RegistrationRenewal::class);

        $renewal = $this->renewalService->start($this->authenticatedUser($request), $request->validated('reason'));

        return to_route('candidate.registration-renewals.show', $renewal)
            ->with('success', 'Renovação iniciada. Confirme apenas os dados que pretende atualizar.');
    }

    public function show(RegistrationRenewal $registrationRenewal): View
    {
        Gate::authorize('view', $registrationRenewal);

        return view('candidate.registration-renewals.show', compact('registrationRenewal'));
    }

    public function update(UpdateRegistrationRenewalRequest $request, RegistrationRenewal $registrationRenewal): RedirectResponse
    {
        Gate::authorize('update', $registrationRenewal);

        $this->renewalService->update($this->authenticatedUser($request), $registrationRenewal, $request->validated());

        return to_route('candidate.registration-renewals.show', $registrationRenewal)
            ->with('success', 'Dados de renovação atualizados.');
    }

    public function submit(SubmitRegistrationRenewalRequest $request, RegistrationRenewal $registrationRenewal): RedirectResponse
    {
        Gate::authorize('update', $registrationRenewal);

        $this->renewalService->submit($this->authenticatedUser($request), $registrationRenewal);

        return to_route('candidate.registration-renewals.show', $registrationRenewal)
            ->with('success', 'Renovação concluída e dados atualizados.');
    }
}
