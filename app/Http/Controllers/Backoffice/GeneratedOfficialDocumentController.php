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
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedOfficialDocumentController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', GeneratedOfficialDocument::class);
        $actor = $this->currentUser();

        return view('backoffice.official-documents.index', [
            'documents' => $this->municipalScope
                ->generatedOfficialDocuments(GeneratedOfficialDocument::query(), $actor)
                ->with(['recipient', 'template'])
                ->latest()
                ->paginate(20),
            'templates' => $this->municipalScope
                ->documentTemplates(DocumentTemplate::query(), $actor)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'users' => $this->municipalScope
                ->users(User::query(), $actor)
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ]);
    }

    public function generate(GenerateOfficialDocumentRequest $request, OfficialDocumentGenerationService $service): RedirectResponse
    {
        Gate::authorize('createBackoffice', GeneratedOfficialDocument::class);

        $data = $request->validated();

        $template = DocumentTemplate::query()->findOrFail((int) $data['document_template_id']);
        Gate::authorize('viewBackoffice', $template);

        $recipient = null;

        if (filled($data['recipient_user_id'] ?? null)) {
            $recipient = User::query()->findOrFail((int) $data['recipient_user_id']);
            abort_unless(
                $this->municipalScope->ownsUser($this->authenticatedUser($request), $recipient),
                404,
            );
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
        Gate::authorize('viewBackoffice', $generatedOfficialDocument);

        return view('backoffice.official-documents.show', compact('generatedOfficialDocument'));
    }

    public function download(GeneratedOfficialDocument $generatedOfficialDocument, OfficialDocumentDownloadService $service): StreamedResponse
    {
        Gate::authorize('downloadBackoffice', $generatedOfficialDocument);

        return $service->download($generatedOfficialDocument, $this->currentUser());
    }

    public function issue(GeneratedOfficialDocument $generatedOfficialDocument, OfficialDocumentGenerationService $service): RedirectResponse
    {
        Gate::authorize('issueBackoffice', $generatedOfficialDocument);
        $service->issue($generatedOfficialDocument, $this->currentUser());

        return back()->with('success', 'Documento emitido.');
    }

    public function cancel(CancelGeneratedOfficialDocumentRequest $request, GeneratedOfficialDocument $generatedOfficialDocument, OfficialDocumentGenerationService $service): RedirectResponse
    {
        Gate::authorize('cancelBackoffice', $generatedOfficialDocument);
        $service->cancel($generatedOfficialDocument, $this->authenticatedUser($request), $request->validated('cancellation_reason'));

        return back()->with('success', 'Documento cancelado.');
    }
}
