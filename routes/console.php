<?php

use App\Enums\ReportExportStatus;
use App\Jobs\ExpireApplicationResultExport;
use App\Models\ReportExport;
use App\Services\Administrative\AdministrativeDeadlineService;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'corrections:expire',
    function (
        AdministrativeDeadlineService $deadlines,
    ): void {
        $expired = $deadlines->markOverdueCorrections();

        $this->info(
            $expired->count()
            .' pedido(s) de aperfeiçoamento expirado(s).',
        );
    },
)->purpose(
    'Marca pedidos de aperfeiçoamento cujo prazo terminou.',
);

Schedule::command('corrections:expire')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Artisan::command('reports:expire-temporal-exports', function (): void {
    $count = 0;
    ReportExport::query()
        ->where('export_profile', TemporalApplicationResultExportService::PROFILE)
        ->where(function ($query): void {
            $query
                ->where(function ($completed): void {
                    $completed
                        ->where('status', ReportExportStatus::Completed->value)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                })
                ->orWhere(function ($expired): void {
                    $expired
                        ->where('status', ReportExportStatus::Expired->value)
                        ->where('file_path', '!=', '');
                });
        })
        ->select('id')
        ->chunkById(100, function ($exports) use (&$count): void {
            foreach ($exports as $export) {
                ExpireApplicationResultExport::dispatch((int) $export->getKey());
                $count++;
            }
        });

    $this->info($count.' exportação(ões) temporal(is) enviada(s) para expiração.');
})->purpose('Expira pacotes temporais de candidaturas e elimina os artefactos privados.');

Schedule::command('reports:expire-temporal-exports')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
