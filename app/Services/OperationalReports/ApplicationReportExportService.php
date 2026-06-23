<?php

namespace App\Services\OperationalReports;

use App\Models\ApplicationReport;
use Illuminate\Support\Facades\Storage;

class ApplicationReportExportService
{
    public function export(ApplicationReport $report): string
    {
        $format = $report->format;
        $extension = $format->storageExtension();
        $path = 'backoffice/application-reports/'.$report->report_number.'.'.$extension;

        $content = $extension === 'csv'
            ? $this->csv($report)
            : $this->html($report);

        Storage::disk('local')->put($path, $content);

        return $path;
    }

    private function html(ApplicationReport $report): string
    {
        $payload = $this->payload($report);
        $application = $this->section($payload, 'application');
        $contest = $this->section($payload, 'contest');

        return '<!doctype html><html lang="pt"><meta charset="utf-8"><title>'.e($report->title).'</title>'
            .'<body><h1>'.e($report->title).'</h1>'
            .'<p><strong>Número de candidatura:</strong> '.e((string) ($application['application_number'] ?? 'n/d')).'</p>'
            .'<p><strong>Número de processo:</strong> '.e((string) ($application['process_number'] ?? 'por gerar')).'</p>'
            .'<p><strong>Concurso:</strong> '.e((string) ($contest['title'] ?? 'n/d')).'</p>'
            .'<p>'.e((string) ($payload['copy'] ?? '')).'</p>'
            .'<pre>'.e(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}').'</pre>'
            .'</body></html>';
    }

    private function csv(ApplicationReport $report): string
    {
        $payload = $this->payload($report);
        $application = $this->section($payload, 'application');

        return implode(',', ['campo', 'valor'])."\n"
            .'application_number,'.str_replace(',', ' ', (string) ($application['application_number'] ?? ''))."\n"
            .'process_number,'.str_replace(',', ' ', (string) ($application['process_number'] ?? ''))."\n"
            .'status,'.str_replace(',', ' ', (string) ($application['status'] ?? ''))."\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ApplicationReport $report): array
    {
        return $report->payload ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function section(array $payload, string $key): array
    {
        $section = $payload[$key] ?? [];

        return is_array($section) ? $section : [];
    }
}
