<?php

namespace App\Http\Controllers\Backoffice\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\PublicPortal\StoreHousingUnitPublicDocumentRequest;
use App\Http\Requests\Backoffice\PublicPortal\UpdateHousingUnitPublicDocumentRequest;
use App\Models\HousingUnit;
use App\Models\HousingUnitPublicDocument;
use App\Services\PublicPortal\PublicHousingPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class HousingUnitPublicDocumentController extends Controller
{
    public function store(
        StoreHousingUnitPublicDocumentRequest $request,
        HousingUnit $housingUnit,
        PublicHousingPublicationService $publicationService,
    ): RedirectResponse {
        Gate::authorize('updatePublicProfile', $housingUnit);

        $publicationService->storeDocument(
            $housingUnit,
            $request->file('document'),
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return back()->with('success', 'Documento público adicionado.');
    }

    public function update(UpdateHousingUnitPublicDocumentRequest $request, HousingUnitPublicDocument $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $data = $request->validated();
        $data['is_public'] = $request->boolean('is_public');
        $data['published_at'] = $data['is_public'] ? ($document->published_at ?? now()) : null;

        $document->update($data);

        return back()->with('success', 'Documento público atualizado.');
    }

    public function destroy(HousingUnitPublicDocument $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('success', 'Documento público removido.');
    }
}
