<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTemplateVersionRequest;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Services\Documents\DocumentTemplateVersionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentTemplateVersionController extends Controller
{
    public function __construct(private readonly DocumentTemplateVersionService $service) {}

    public function store(StoreDocumentTemplateVersionRequest $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        Gate::authorize('update', $documentTemplate);
        $version = $this->service->create($documentTemplate, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.document-template-versions.show', $version);
    }

    public function show(DocumentTemplateVersion $documentTemplateVersion): View
    {
        Gate::authorize('view', $documentTemplateVersion);

        return view('backoffice.document-templates.version', compact('documentTemplateVersion'));
    }

    public function approve(DocumentTemplateVersion $documentTemplateVersion): RedirectResponse
    {
        Gate::authorize('approve', $documentTemplateVersion);
        $this->service->approve($documentTemplateVersion, $this->currentUser());

        return back()->with('success', 'Versão aprovada.');
    }

    public function activate(DocumentTemplateVersion $documentTemplateVersion): RedirectResponse
    {
        Gate::authorize('approve', $documentTemplateVersion);
        $this->service->activate($documentTemplateVersion, $this->currentUser());

        return back()->with('success', 'Versão ativada.');
    }
}
