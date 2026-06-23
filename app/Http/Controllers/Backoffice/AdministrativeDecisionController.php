<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveAdministrativeDecisionRequest;
use App\Http\Requests\StoreAdministrativeDecisionRequest;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Services\Administrative\AdministrativeDecisionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdministrativeDecisionController extends Controller
{
    public function __construct(private readonly AdministrativeDecisionService $decisionService) {}

    public function createAdmission(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('create', AdministrativeDecision::class);

        return view('backoffice.administrative-decisions.create-admission', ['process' => $administrativeProcess]);
    }

    public function storeAdmission(StoreAdministrativeDecisionRequest $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('create', AdministrativeDecision::class);
        $decision = $this->decisionService->create($administrativeProcess, $this->decisionService->admissionData($request->validated()), $this->authenticatedUser($request));

        return to_route('backoffice.administrative-decisions.show', $decision)
            ->with('success', 'Decisão de admissão registada.');
    }

    public function createNonAdmission(AdministrativeProcess $administrativeProcess): View
    {
        Gate::authorize('create', AdministrativeDecision::class);

        return view('backoffice.administrative-decisions.create-non-admission', ['process' => $administrativeProcess]);
    }

    public function storeNonAdmission(StoreAdministrativeDecisionRequest $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('create', AdministrativeDecision::class);
        $decision = $this->decisionService->create($administrativeProcess, $this->decisionService->nonAdmissionData($request->validated()), $this->authenticatedUser($request));

        return to_route('backoffice.administrative-decisions.show', $decision)
            ->with('success', 'Decisão de não admissão registada.');
    }

    public function show(AdministrativeDecision $administrativeDecision): View
    {
        Gate::authorize('view', $administrativeDecision);
        $administrativeDecision->load(['administrativeProcess', 'application', 'decidedBy', 'approvedBy']);

        return view('backoffice.administrative-decisions.show', ['decision' => $administrativeDecision]);
    }

    public function approve(ApproveAdministrativeDecisionRequest $request, AdministrativeDecision $administrativeDecision): RedirectResponse
    {
        Gate::authorize('approve', $administrativeDecision);
        $this->decisionService->approve($administrativeDecision, $this->authenticatedUser($request));

        return back()->with('success', 'Decisão aprovada e aplicada ao processo.');
    }

    public function cancel(Request $request, AdministrativeDecision $administrativeDecision): RedirectResponse
    {
        Gate::authorize('approve', $administrativeDecision);
        $this->decisionService->cancel($administrativeDecision, $this->authenticatedUser($request));

        return back()->with('success', 'Decisão cancelada.');
    }
}
