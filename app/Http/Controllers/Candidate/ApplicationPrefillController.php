<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulator\ApplyApplicationPrefillRequest;
use App\Http\Requests\Simulator\ConfirmApplicationPrefillRequest;
use App\Models\ApplicationPrefill;
use App\Services\Simulator\ApplicationPrefillService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationPrefillController extends Controller
{
    public function __construct(private readonly ApplicationPrefillService $prefillService) {}

    public function show(ApplicationPrefill $applicationPrefill): View
    {
        Gate::authorize('view', $applicationPrefill);

        $applicationPrefill->load(['application.contest', 'simulationSession.result', 'candidateDataReuseProfile']);

        return view('candidate.application-prefills.show', compact('applicationPrefill'));
    }

    public function confirm(ConfirmApplicationPrefillRequest $request, ApplicationPrefill $applicationPrefill): RedirectResponse
    {
        Gate::authorize('update', $applicationPrefill);

        $this->prefillService->confirm($this->authenticatedUser($request), $applicationPrefill);

        return to_route('candidate.application-prefills.show', $applicationPrefill)
            ->with('success', 'Dados confirmados. Pode agora aplicar o pré-preenchimento ao rascunho.');
    }

    public function apply(ApplyApplicationPrefillRequest $request, ApplicationPrefill $applicationPrefill): RedirectResponse
    {
        Gate::authorize('update', $applicationPrefill);

        $this->prefillService->apply($this->authenticatedUser($request), $applicationPrefill);

        return to_route('candidate.application-prefills.show', $applicationPrefill)
            ->with('success', 'Pré-preenchimento aplicado ao rascunho da candidatura.');
    }

    public function cancel(Request $request, ApplicationPrefill $applicationPrefill): RedirectResponse
    {
        Gate::authorize('update', $applicationPrefill);

        $this->prefillService->cancel($this->authenticatedUser($request), $applicationPrefill);

        return to_route('candidate.application-prefills.show', $applicationPrefill)
            ->with('success', 'Pré-preenchimento cancelado.');
    }
}
