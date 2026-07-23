<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\Document;
use App\Models\HousingApplication;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', Document::class);
        $documents = $this->municipalScope->documents(
            Document::query(),
            $this->authenticatedUser($request),
        )
            ->with(['citizen', 'housingApplication.citizen', 'contract.citizen', 'contract.housingUnit'])
            ->latest()
            ->paginate(15);

        return view('documents.index', compact('documents'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', Document::class);
        $actor = $this->authenticatedUser($request);
        $citizens = $this->municipalScope->citizens(Citizen::query(), $actor)
            ->orderBy('name')
            ->get(['id', 'name']);
        $applications = $this->municipalScope->housingApplications(HousingApplication::query(), $actor)
            ->with('citizen:id,name')
            ->latest()
            ->get(['id', 'citizen_id', 'status']);
        $contracts = $this->municipalScope->contracts(Contract::query(), $actor)
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);

        return view('documents.create', compact('citizens', 'applications', 'contracts'));
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', Document::class);
        $validated = $request->validated();
        $uploadedFile = $request->file('file');

        $validated['path'] = $uploadedFile->store('documents', 'local');
        $validated['mime_type'] = $uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType();
        $validated['size'] = $uploadedFile->getSize();

        unset($validated['file']);

        $document = new Document($validated);
        $document->forceFill([
            'municipality_id' => $this->authenticatedUser($request)->municipality_id,
        ])->save();
        $this->auditLogger->record(
            AuditEvents::CREATE,
            $document,
            'documents',
            'create',
            'Documento privado criado no âmbito municipal.',
        );

        return to_route('documents.index')
            ->with('success', 'Documento criado com sucesso.');
    }

    public function show(Request $request, Document $document): View
    {
        Gate::authorize('viewBackoffice', $document);
        $document->load(['citizen', 'housingApplication.citizen', 'contract.citizen', 'contract.housingUnit']);

        return view('documents.show', compact('document'));
    }

    public function edit(Request $request, Document $document): View
    {
        Gate::authorize('updateBackoffice', $document);
        $actor = $this->authenticatedUser($request);
        $citizens = $this->municipalScope->citizens(Citizen::query(), $actor)
            ->orderBy('name')
            ->get(['id', 'name']);
        $applications = $this->municipalScope->housingApplications(HousingApplication::query(), $actor)
            ->with('citizen:id,name')
            ->latest()
            ->get(['id', 'citizen_id', 'status']);
        $contracts = $this->municipalScope->contracts(Contract::query(), $actor)
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);

        return view('documents.edit', compact('document', 'citizens', 'applications', 'contracts'));
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $document);
        $validated = $request->validated();
        $previousPath = $document->path;

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $validated['path'] = $uploadedFile->store('documents', 'local');
            $validated['mime_type'] = $uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType();
            $validated['size'] = $uploadedFile->getSize();
        }

        unset($validated['file']);

        $document->update($validated);
        if (($validated['path'] ?? null) !== null && $previousPath !== $validated['path']) {
            Storage::disk('local')->delete($previousPath);
        }
        $this->auditLogger->record(
            AuditEvents::UPDATE,
            $document,
            'documents',
            'update',
            'Documento privado atualizado no âmbito municipal.',
        );

        return to_route('documents.index')
            ->with('success', 'Documento atualizado com sucesso.');
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $document);
        Storage::disk('local')->delete($document->path);
        $document->delete();
        $this->auditLogger->record(
            AuditEvents::DELETE,
            $document,
            'documents',
            'delete',
            'Documento privado removido no âmbito municipal.',
        );

        return to_route('documents.index')
            ->with('success', 'Documento eliminado com sucesso.');
    }
}
