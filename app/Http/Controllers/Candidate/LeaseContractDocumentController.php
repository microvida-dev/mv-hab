<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\LeaseContractDocument;
use App\Services\Contracts\LeaseContractDocumentService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseContractDocumentController extends Controller
{
    public function __construct(private readonly LeaseContractDocumentService $service) {}

    public function download(LeaseContractDocument $leaseContractDocument): StreamedResponse
    {
        Gate::authorize('download', $leaseContractDocument);

        return $this->service->download($leaseContractDocument, $this->currentUser());
    }
}
