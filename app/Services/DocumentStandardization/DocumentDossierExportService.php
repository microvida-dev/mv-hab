<?php

namespace App\Services\DocumentStandardization;

use App\Enums\DocumentDossierItemStatus;
use App\Models\DocumentDossier;
use App\Models\DocumentDossierItem;
use Illuminate\Support\Facades\Storage;

class DocumentDossierExportService
{
    public function export(DocumentDossier $dossier): string
    {
        $dossier->loadMissing('items');
        $path = 'backoffice/document-dossiers/'.$dossier->dossier_number.'.html';

        $rows = $dossier->items
            ->map(fn (DocumentDossierItem $item): string => '<tr><td>'.e($item->sort_order).'</td><td>'.e($item->category).'</td><td>'.e($item->label).'</td><td>'.e($this->statusLabel($item)).'</td></tr>')
            ->implode('');
        $html = '<!doctype html><html lang="pt"><meta charset="utf-8"><title>'.e($dossier->title).'</title>'
            .'<body><h1>'.e($dossier->title).'</h1><p>'.e((string) $dossier->summary).'</p>'
            .'<table><thead><tr><th>#</th><th>Categoria</th><th>Documento</th><th>Estado</th></tr></thead><tbody>'.$rows.'</tbody></table>'
            .'</body></html>';

        Storage::disk('local')->put($path, $html);

        return $path;
    }

    private function statusLabel(DocumentDossierItem $item): string
    {
        $status = $item->getAttribute('status');

        return $status instanceof DocumentDossierItemStatus
            ? $status->label()
            : (string) $status;
    }
}
