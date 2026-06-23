<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Models\HousingUnit;
use App\Models\HousingUnitPublicDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicHousingDocumentController extends Controller
{
    public function download(HousingUnitPublicDocument $document): StreamedResponse
    {
        $document->load('housingUnit');

        abort_unless($document->isDownloadable(), 404);
        abort_unless(HousingUnit::query()->publiclyVisible()->whereKey($document->housing_unit_id)->exists(), 404);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        $document->increment('download_count');

        return Storage::disk($document->disk)->download(
            $document->path,
            Str::slug($document->title).'.pdf',
            ['Content-Type' => $document->mime_type ?: 'application/pdf'],
        );
    }
}
