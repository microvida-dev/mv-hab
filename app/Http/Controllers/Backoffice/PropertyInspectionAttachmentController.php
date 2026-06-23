<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyInspectionAttachmentRequest;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionAttachment;
use App\Services\Inspections\PropertyInspectionAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PropertyInspectionAttachmentController extends Controller
{
    public function __construct(private readonly PropertyInspectionAttachmentService $attachments) {}

    public function store(StorePropertyInspectionAttachmentRequest $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('create', PropertyInspectionAttachment::class);
        $this->attachments->store($propertyInspection, $request->file('attachment'), $this->authenticatedUser($request), (bool) $request->boolean('visible_to_tenant'), $request->integer('property_inspection_item_id') ?: null);

        return back()->with('success', 'Anexo carregado.');
    }

    public function download(PropertyInspectionAttachment $propertyInspectionAttachment): StreamedResponse
    {
        Gate::authorize('download', $propertyInspectionAttachment);

        return $this->attachments->download($propertyInspectionAttachment, $this->currentUser());
    }
}
