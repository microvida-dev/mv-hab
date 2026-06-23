<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\GeneratedOfficialDocument;
use App\Services\Documents\OfficialDocumentDownloadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedOfficialDocumentController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.official-documents.index', [
            'documents' => GeneratedOfficialDocument::query()
                ->where('recipient_user_id', $this->authenticatedUser($request)->id)
                ->whereIn('status', ['generated', 'issued'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(GeneratedOfficialDocument $generatedOfficialDocument): View
    {
        Gate::authorize('view', $generatedOfficialDocument);

        return view('candidate.official-documents.show', compact('generatedOfficialDocument'));
    }

    public function download(GeneratedOfficialDocument $generatedOfficialDocument, OfficialDocumentDownloadService $service): StreamedResponse
    {
        Gate::authorize('view', $generatedOfficialDocument);

        return $service->download($generatedOfficialDocument, $this->currentUser());
    }
}
