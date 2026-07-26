<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\GeneratedProcedureDocument;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\ProcedureTemplates\GeneratedProcedureDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedProcedureDocumentController extends Controller
{
    public function __construct(
        private readonly GeneratedProcedureDocumentService $documents,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', GeneratedProcedureDocument::class);
        $documents = $this->municipalScope
            ->generatedProcedureDocuments(
                GeneratedProcedureDocument::query(),
                $this->currentUser(),
            )
            ->latest()
            ->paginate(20);

        return view('backoffice.generated-documents.index', compact('documents'));
    }

    public function show(GeneratedProcedureDocument $generatedProcedureDocument): View
    {
        Gate::authorize('viewBackoffice', $generatedProcedureDocument);

        return view('backoffice.generated-documents.show', compact('generatedProcedureDocument'));
    }

    public function download(GeneratedProcedureDocument $generatedProcedureDocument): StreamedResponse
    {
        Gate::authorize('downloadBackoffice', $generatedProcedureDocument);

        return $this->documents->download($generatedProcedureDocument, $this->currentUser());
    }

    public function issue(GeneratedProcedureDocument $generatedProcedureDocument): RedirectResponse
    {
        Gate::authorize('issueBackoffice', $generatedProcedureDocument);
        $this->documents->approve($generatedProcedureDocument, $this->currentUser());

        return back()->with('success', 'Documento aprovado.');
    }
}
