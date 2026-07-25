<?php

namespace App\Services\DocumentStandardization;

use App\Enums\DocumentDossierItemStatus;
use App\Models\DocumentDossier;
use App\Models\DocumentDossierItem;
use Illuminate\Support\Facades\Storage;

final class DocumentDossierExportService
{
    public function export(DocumentDossier $dossier): string
    {
        $dossier->loadMissing('items');

        $path = 'backoffice/document-dossiers/'
            .$dossier->dossier_number
            .'.html';

        $rows = $dossier->items
            ->map(
                fn (DocumentDossierItem $item): string => $this->row($item),
            )
            ->implode('');

        $html = '<!doctype html>'
            .'<html lang="pt">'
            .'<head>'
            .'<meta charset="utf-8">'
            .'<title>'.e($dossier->title).'</title>'
            .'<style>'
            .'body{font-family:Arial,sans-serif;color:#1f2937;}'
            .'table{width:100%;border-collapse:collapse;}'
            .'th,td{border:1px solid #d1d5db;padding:8px;text-align:left;}'
            .'th{background:#f3f4f6;}'
            .'</style>'
            .'</head>'
            .'<body>'
            .'<h1>'.e($dossier->title).'</h1>'
            .'<p>'.e((string) $dossier->summary).'</p>'
            .'<table>'
            .'<thead>'
            .'<tr>'
            .'<th>#</th>'
            .'<th>Categoria</th>'
            .'<th>Documento</th>'
            .'<th>Âmbito</th>'
            .'<th>Posição</th>'
            .'<th>Período</th>'
            .'<th>Estado</th>'
            .'</tr>'
            .'</thead>'
            .'<tbody>'.$rows.'</tbody>'
            .'</table>'
            .'</body>'
            .'</html>';

        Storage::disk('local')->put($path, $html);

        return $path;
    }

    private function row(DocumentDossierItem $item): string
    {
        return '<tr>'
            .'<td>'.e($item->sort_order).'</td>'
            .'<td>'.e($item->category).'</td>'
            .'<td>'.e($item->label).'</td>'
            .'<td>'.e($item->target_label ?? '—').'</td>'
            .'<td>'.e($item->positionLabel() ?? '—').'</td>'
            .'<td>'.e($item->referencePeriodLabel() ?? '—').'</td>'
            .'<td>'.e($this->statusLabel($item)).'</td>'
            .'</tr>';
    }

    private function statusLabel(
        DocumentDossierItem $item,
    ): string {
        $status = $item->getAttribute('status');

        return $status instanceof DocumentDossierItemStatus
            ? $status->label()
            : (string) $status;
    }
}
