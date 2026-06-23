<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLeaseContractValidationRequest;
use App\Http\Requests\ValidateLeaseContractRequest;
use App\Models\Contract;
use App\Models\LeaseContractValidation;
use App\Services\Contracts\LeaseContractValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LeaseContractValidationController extends Controller
{
    public function __construct(private readonly LeaseContractValidationService $service) {}

    public function store(ValidateLeaseContractRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('create', LeaseContractValidation::class);
        $this->service->approve($leaseContract, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Validação interna aprovada.');
    }

    public function approve(ValidateLeaseContractRequest $request, LeaseContractValidation $leaseContractValidation): RedirectResponse
    {
        Gate::authorize('approve', $leaseContractValidation);
        $leaseContract = $leaseContractValidation->leaseContract;
        abort_unless($leaseContract instanceof Contract, 500);

        $this->service->approve($leaseContract, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Validação interna aprovada.');
    }

    public function reject(RejectLeaseContractValidationRequest $request, LeaseContractValidation $leaseContractValidation): RedirectResponse
    {
        Gate::authorize('reject', $leaseContractValidation);
        $this->service->reject($leaseContractValidation, $this->authenticatedUser($request), $request->validated('rejection_reason'));

        return back()->with('success', 'Validação rejeitada.');
    }
}
