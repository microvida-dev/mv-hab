<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateDocumentDossierRequest;
use App\Models\Application;
use App\Models\DocumentDossier;
use App\Services\DocumentStandardization\DocumentDossierService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDossierController extends Controller
{
    public function __construct(private readonly DocumentDossierService $dossiers) {}

    public function show(Application $application): View
    {
        Gate::authorize('viewAny', DocumentDossier::class);
        $dossiers = $application->documentDossiers()->with('items')->latest()->paginate(10);

        return view('backoffice.document-dossiers.show', compact('application', 'dossiers'));
    }

    public function generate(GenerateDocumentDossierRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('create', DocumentDossier::class);
        $dossier = $this->dossiers->generate($application, $this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.applications.document-dossier.show', $application)->with('success', 'Dossier gerado: '.$dossier->dossier_number);
    }

    public function download(DocumentDossier $documentDossier): StreamedResponse
    {
        Gate::authorize('download', $documentDossier);
        abort_if($documentDossier->file_path === null || ! Storage::disk('local')->exists($documentDossier->file_path), 404);

        return Storage::disk('local')->download($documentDossier->file_path, $documentDossier->dossier_number.'.html');
    }
}
