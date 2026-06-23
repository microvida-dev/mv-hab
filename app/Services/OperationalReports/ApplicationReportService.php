<?php

namespace App\Services\OperationalReports;

use App\Enums\ApplicationReportStatus;
use App\Enums\ReportFormat;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class ApplicationReportService
{
    public function __construct(
        private readonly ApplicationReportPayloadBuilder $payloadBuilder,
        private readonly ApplicationReportExportService $exporter,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(Application $application, User $actor, array $options): ApplicationReport
    {
        return DB::transaction(function () use ($application, $actor, $options): ApplicationReport {
            $format = ReportFormat::tryFrom((string) ($options['format'] ?? ReportFormat::Html->value)) ?? ReportFormat::Html;
            $payload = $this->payloadBuilder->build($application, $actor, $options);

            $report = new ApplicationReport([
                'format' => $format,
                'title' => 'Relatório da candidatura '.$application->application_number,
                'summary' => 'Relatório operacional gerado para análise municipal.',
            ]);
            $report->forceFill([
                'report_number' => $this->number(),
                'application_id' => $application->id,
                'contest_id' => $application->contest_id,
                'user_id' => $application->user_id,
                'status' => ApplicationReportStatus::Generated,
                'payload' => $payload,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ])->save();

            $report->forceFill(['file_path' => $this->exporter->export($report)])->save();

            $this->auditLogger->record(
                AuditEvents::EXPORT,
                $report,
                'reports',
                'application_report_generate',
                'Relatório por candidatura gerado.',
                metadata: ['application_id' => $application->id, 'format' => $format->value],
            );

            return $report->refresh();
        });
    }

    private function number(): string
    {
        $next = ApplicationReport::withTrashed()->count() + 1;

        do {
            $number = 'RCA-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (ApplicationReport::withTrashed()->where('report_number', $number)->exists());

        return $number;
    }
}
