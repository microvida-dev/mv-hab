<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\DocumentAccessAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReplaceDocumentSubmissionRequest;
use App\Http\Requests\StoreDocumentSubmissionRequest;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Services\Documents\DocumentAccessService;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Documents\DocumentUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentChecklistService $checklistService,
        private readonly DocumentUploadService $uploadService,
        private readonly DocumentAccessService $accessService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return to_route('candidate.registration.create')
                ->with('info', 'Crie o Registo de Adesão antes de consultar documentos.');
        }

        $submissions = $registration->documentSubmissions()
            ->with(['documentType', 'requiredDocument', 'currentVersion'])
            ->latest()
            ->paginate(10);
        $checklist = $this->checklistService->forRegistration($registration);

        return view('candidate.documents.index', compact('registration', 'submissions', 'checklist'));
    }

    public function show(Request $request, DocumentSubmission $documentSubmission): View
    {
        Gate::authorize('view', $documentSubmission);

        $documentSubmission->load([
            'documentType',
            'requiredDocument',
            'versions.uploadedBy',
            'reviews.reviewedBy',
            'currentVersion',
        ]);
        $version = $documentSubmission->currentVersion;

        $this->accessService->record($documentSubmission, DocumentAccessAction::View, $version, $this->authenticatedUser($request));

        return view('candidate.documents.show', ['submission' => $documentSubmission]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            return to_route('candidate.registration.create');
        }

        Gate::authorize('create', DocumentSubmission::class);

        $application = null;

        if ($request->filled('application')) {
            $application = Application::query()
                ->forUser($this->authenticatedUser($request))
                ->where('public_id', $request->query('application'))
                ->firstOrFail();
            Gate::authorize('update', $application);
        }

        $checklist = $application
            ? $this->checklistService->forApplication($application)
            : $this->checklistService->forRegistration($registration);
        $rawItems = $checklist['items'] ?? [];
        $itemsArray = is_array($rawItems)
            ? array_values(array_filter($rawItems, fn ($item): bool => is_array($item)))
            : [];
        /** @var Collection<int, array<string, mixed>> $items */
        $items = new Collection($itemsArray);
        $item = $this->selectedChecklistItem($items, $request);
        abort_if($item === null, 404);

        return view('candidate.documents.create', compact('registration', 'item', 'application'));
    }

    public function store(StoreDocumentSubmissionRequest $request): RedirectResponse
    {
        $registration = $this->authenticatedUser($request)->adhesionRegistration()->firstOrFail();

        $submission = $this->uploadService->store(
            registration: $registration,
            file: $request->file('file'),
            data: $request->validated(),
            actor: $this->authenticatedUser($request),
        );

        return to_route('candidate.documents.show', $submission)
            ->with('success', 'Documento submetido com sucesso.');
    }

    public function replaceCreate(DocumentSubmission $documentSubmission): View
    {
        Gate::authorize('replace', $documentSubmission);

        $documentSubmission->load(['documentType', 'requiredDocument', 'currentVersion']);

        return view('candidate.documents.replace', ['submission' => $documentSubmission]);
    }

    public function replaceStore(
        ReplaceDocumentSubmissionRequest $request,
        DocumentSubmission $documentSubmission,
    ): RedirectResponse {
        $submission = $this->uploadService->replace(
            submission: $documentSubmission,
            file: $request->file('file'),
            data: $request->validated(),
            actor: $this->authenticatedUser($request),
        );

        return to_route('candidate.documents.show', $submission)
            ->with('success', 'Documento substituído com sucesso.');
    }

    public function download(Request $request, DocumentSubmission $documentSubmission): StreamedResponse
    {
        Gate::authorize('download', $documentSubmission);

        return $this->accessService->download($documentSubmission->load('currentVersion'), $this->authenticatedUser($request));
    }

    public function destroy(Request $request, DocumentSubmission $documentSubmission): RedirectResponse
    {
        Gate::authorize('delete', $documentSubmission);
        $this->uploadService->cancel($documentSubmission, $this->authenticatedUser($request));

        return to_route('candidate.documents.index')
            ->with('success', 'Documento cancelado. O histórico foi preservado.');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<string, mixed>|null
     */
    private function selectedChecklistItem(Collection $items, Request $request): ?array
    {
        if ($request->filled('item')) {
            $item = $items->firstWhere('key', $request->query('item'));

            return is_array($item) ? $item : null;
        }

        return $items->first(function (array $item) use ($request) {
            return (int) $item['required_document_id'] === $request->integer('required_document_id')
                && $item['target_type'] === $request->query('target_type')
                && (int) $item['target_id'] === $request->integer('target_id');
        });
    }
}
