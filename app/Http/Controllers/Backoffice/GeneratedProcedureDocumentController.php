<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\GeneratedProcedureDocument;
use App\Services\ProcedureTemplates\GeneratedProcedureDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedProcedureDocumentController extends Controller
{
    public function __construct(private readonly GeneratedProcedureDocumentService $documents) {}

    public function index(): View
    {
        Gate::authorize('viewAny', GeneratedProcedureDocument::class);
        $documents = GeneratedProcedureDocument::query()->latest()->paginate(20);

        return view('backoffice.generated-documents.index', compact('documents'));
    }

    public function show(GeneratedProcedureDocument $generatedProcedureDocument): View
    {
        Gate::authorize('view', $generatedProcedureDocument);

        return view('backoffice.generated-documents.show', compact('generatedProcedureDocument'));
    }

    public function download(GeneratedProcedureDocument $generatedProcedureDocument): StreamedResponse
    {
        Gate::authorize('download', $generatedProcedureDocument);
        abort_if($generatedProcedureDocument->file_path === null || ! Storage::disk('local')->exists($generatedProcedureDocument->file_path), 404);

        return Storage::disk('local')->download($generatedProcedureDocument->file_path, $generatedProcedureDocument->document_number.'.html');
    }

    public function issue(GeneratedProcedureDocument $generatedProcedureDocument): RedirectResponse
    {
        Gate::authorize('approve', $generatedProcedureDocument);
        $this->documents->approve($generatedProcedureDocument, $this->currentUser());

        return back()->with('success', 'Documento aprovado.');
    }
}
