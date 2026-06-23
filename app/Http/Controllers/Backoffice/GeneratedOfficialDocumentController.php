<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelGeneratedOfficialDocumentRequest;
use App\Http\Requests\GenerateOfficialDocumentRequest;
use App\Models\DocumentTemplate;
use App\Models\GeneratedOfficialDocument;
use App\Models\User;
use App\Services\Documents\OfficialDocumentDownloadService;
use App\Services\Documents\OfficialDocumentGenerationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedOfficialDocumentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', GeneratedOfficialDocument::class);

        return view('backoffice.official-documents.index', [
            'documents' => GeneratedOfficialDocument::query()->with(['recipient', 'template'])->latest()->paginate(20),
            'templates' => DocumentTemplate::query()->where('status', 'active')->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function generate(GenerateOfficialDocumentRequest $request, OfficialDocumentGenerationService $service): RedirectResponse
    {
        Gate::authorize('create', GeneratedOfficialDocument::class);

        $data = $request->validated();

        $template = DocumentTemplate::query()->findOrFail((int) $data['document_template_id']);

        $recipient = null;

        if (filled($data['recipient_user_id'] ?? null)) {
            $recipient = User::query()->findOrFail((int) $data['recipient_user_id']);
        }

        $document = $service->generate(
            $template,
            $data['variables'] ?? [],
            $this->authenticatedUser($request),
            $recipient,
            issueImmediately: (bool) ($data['issue_immediately'] ?? false),
        );

        return to_route('backoffice.official-documents.show', $document);
    }

    public function show(GeneratedOfficialDocument $generatedOfficialDocument): View
    {
        Gate::authorize('view', $generatedOfficialDocument);

        return view('backoffice.official-documents.show', compact('generatedOfficialDocument'));
    }

    public function download(GeneratedOfficialDocument $generatedOfficialDocument, OfficialDocumentDownloadService $service): StreamedResponse
    {
        Gate::authorize('view', $generatedOfficialDocument);

        return $service->download($generatedOfficialDocument, $this->currentUser());
    }

    public function issue(GeneratedOfficialDocument $generatedOfficialDocument, OfficialDocumentGenerationService $service): RedirectResponse
    {
        Gate::authorize('update', $generatedOfficialDocument);
        $service->issue($generatedOfficialDocument, $this->currentUser());

        return back()->with('success', 'Documento emitido.');
    }

    public function cancel(CancelGeneratedOfficialDocumentRequest $request, GeneratedOfficialDocument $generatedOfficialDocument, OfficialDocumentGenerationService $service): RedirectResponse
    {
        Gate::authorize('update', $generatedOfficialDocument);
        $service->cancel($generatedOfficialDocument, $this->authenticatedUser($request), $request->validated('cancellation_reason'));

        return back()->with('success', 'Documento cancelado.');
    }
}
