<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateLeaseContractDocumentRequest;
use App\Models\Contract;
use App\Models\LeaseContractDocument;
use App\Services\Contracts\LeaseContractDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseContractDocumentController extends Controller
{
    public function __construct(private readonly LeaseContractDocumentService $service) {}

    public function generate(GenerateLeaseContractDocumentRequest $request, Contract $leaseContract): RedirectResponse
    {
        Gate::authorize('generateDocument', $leaseContract);
        $this->service->generate($leaseContract, $this->authenticatedUser($request));

        return back()->with('success', 'Documento contratual HTML gerado.');
    }

    public function download(LeaseContractDocument $leaseContractDocument): StreamedResponse
    {
        Gate::authorize('download', $leaseContractDocument);

        return $this->service->download($leaseContractDocument, $this->currentUser());
    }
}
