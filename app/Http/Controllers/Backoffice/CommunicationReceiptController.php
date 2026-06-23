<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\CommunicationReceipt;
use App\Services\Notifications\CommunicationReceiptService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunicationReceiptController extends Controller
{
    public function download(CommunicationReceipt $communicationReceipt, CommunicationReceiptService $service): StreamedResponse
    {
        Gate::authorize('view', $communicationReceipt);

        return $service->download($communicationReceipt, $this->currentUser());
    }
}
