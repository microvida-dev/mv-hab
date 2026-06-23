<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\Document;
use App\Models\HousingApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(): View
    {
        $documents = Document::query()
            ->with(['citizen', 'housingApplication.citizen', 'contract.citizen', 'contract.housingUnit'])
            ->latest()
            ->paginate(15);

        return view('documents.index', compact('documents'));
    }

    public function create(): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $applications = HousingApplication::query()
            ->with('citizen:id,name')
            ->latest()
            ->get(['id', 'citizen_id', 'status']);
        $contracts = Contract::query()
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);

        return view('documents.create', compact('citizens', 'applications', 'contracts'));
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $uploadedFile = $request->file('file');

        $validated['path'] = $uploadedFile->store('documents');
        $validated['mime_type'] = $uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType();
        $validated['size'] = $uploadedFile->getSize();

        unset($validated['file']);

        Document::create($validated);

        return to_route('documents.index')
            ->with('success', 'Documento criado com sucesso.');
    }

    public function show(Document $document): View
    {
        $document->load(['citizen', 'housingApplication.citizen', 'contract.citizen', 'contract.housingUnit']);

        return view('documents.show', compact('document'));
    }

    public function edit(Document $document): View
    {
        $citizens = Citizen::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $applications = HousingApplication::query()
            ->with('citizen:id,name')
            ->latest()
            ->get(['id', 'citizen_id', 'status']);
        $contracts = Contract::query()
            ->with(['citizen:id,name', 'housingUnit:id,code'])
            ->latest()
            ->get(['id', 'citizen_id', 'housing_unit_id']);

        return view('documents.edit', compact('document', 'citizens', 'applications', 'contracts'));
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            Storage::delete($document->path);

            $uploadedFile = $request->file('file');
            $validated['path'] = $uploadedFile->store('documents');
            $validated['mime_type'] = $uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType();
            $validated['size'] = $uploadedFile->getSize();
        }

        unset($validated['file']);

        $document->update($validated);

        return to_route('documents.index')
            ->with('success', 'Documento atualizado com sucesso.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Storage::delete($document->path);
        $document->delete();

        return to_route('documents.index')
            ->with('success', 'Documento eliminado com sucesso.');
    }
}
