<?php

namespace App\Services\Reporting\Exporters;

use Illuminate\Support\HtmlString;

class HtmlReportExporter
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     */
    public function render(string $title, array $rows, array $filters): string
    {
        $headers = $rows === [] ? [] : array_keys($rows[0]);
        $head = implode('', array_map(fn ($value) => '<th>'.e($value).'</th>', $headers));
        $body = implode('', array_map(function (array $row) {
            return '<tr>'.implode('', array_map(fn ($value) => '<td>'.e(is_array($value) ? (json_encode($value) ?: '') : $value).'</td>', $row)).'</tr>';
        }, $rows));
        $encodedFilters = json_encode($filters, JSON_UNESCAPED_UNICODE) ?: '';

        return (string) new HtmlString('<!doctype html><html lang="pt"><meta charset="utf-8"><title>'.e($title).'</title><style>body{font-family:Arial,sans-serif;padding:32px;color:#182232}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccd4dc;padding:8px;text-align:left}th{background:#edf4f2}small{color:#667085}</style><h1>'.e($title).'</h1><small>Gerado em '.e(now()->format('d/m/Y H:i')).' | Filtros: '.e($encodedFilters).'</small><table><thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table></html>');
    }
}
