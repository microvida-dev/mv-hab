<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceAttachmentRequest;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceAttachmentController extends Controller
{
    public function __construct(private readonly MaintenanceAttachmentService $attachments) {}

    public function store(StoreMaintenanceAttachmentRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('update', $maintenanceRequest);
        $this->attachments->storeForRequest($maintenanceRequest, $request->file('attachment'), $this->authenticatedUser($request), visibleToTenant: true, description: $request->input('description'));

        return back()->with('success', 'Anexo carregado.');
    }

    public function download(MaintenanceAttachment $maintenanceAttachment): StreamedResponse
    {
        Gate::authorize('download', $maintenanceAttachment);

        return $this->attachments->download($maintenanceAttachment, $this->currentUser());
    }
}
