<?php

namespace App\Http\Controllers\Backoffice\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\PublicPortal\StoreHousingUnitImageRequest;
use App\Http\Requests\Backoffice\PublicPortal\UpdateHousingUnitImageRequest;
use App\Models\HousingUnit;
use App\Models\HousingUnitImage;
use App\Services\PublicPortal\PublicHousingPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class HousingUnitImageController extends Controller
{
    public function store(
        StoreHousingUnitImageRequest $request,
        HousingUnit $housingUnit,
        PublicHousingPublicationService $publicationService,
    ): RedirectResponse {
        Gate::authorize('updatePublicProfile', $housingUnit);

        $publicationService->storeImage(
            $housingUnit,
            $request->file('image'),
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return back()->with('success', 'Imagem pública adicionada.');
    }

    public function update(UpdateHousingUnitImageRequest $request, HousingUnitImage $image): RedirectResponse
    {
        Gate::authorize('update', $image);

        $data = $request->validated();
        $data['is_cover'] = $request->boolean('is_cover');
        $data['is_public'] = $request->boolean('is_public');

        $housingUnit = $image->housingUnit;

        if ($data['is_cover'] && $housingUnit instanceof HousingUnit) {
            $housingUnit->images()->whereKeyNot($image->getKey())->update(['is_cover' => false]);
            $housingUnit->forceFill(['og_image_path' => $image->path])->save();
        }

        $image->update($data);

        return back()->with('success', 'Imagem pública atualizada.');
    }

    public function destroy(HousingUnitImage $image): RedirectResponse
    {
        Gate::authorize('delete', $image);

        Storage::disk($image->disk)->delete($image->path);
        $image->delete();

        return back()->with('success', 'Imagem pública removida.');
    }
}
