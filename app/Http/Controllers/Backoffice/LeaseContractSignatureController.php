<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaseContractSignatureRequest;
use App\Models\Contract;
use App\Models\LeaseContractSignature;
use App\Services\Contracts\LeaseContractSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LeaseContractSignatureController extends Controller
{
    public function __construct(private readonly LeaseContractSignatureService $service) {}

    public function store(StoreLeaseContractSignatureRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('create', LeaseContractSignature::class);
        $this->service->store($leaseContract, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Assinatura/registo manual guardado.');
    }
}
