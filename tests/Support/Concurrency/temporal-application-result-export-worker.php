<?php

declare(strict_types=1);

use App\Models\ReportExport;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$application = require dirname(__DIR__, 3).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

[$script, $payloadPath, $barrierPath, $readyPath, $outputPath] = $argv;
$payload = json_decode(
    (string) file_get_contents($payloadPath),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
touch($readyPath);

$deadline = microtime(true) + 30;

while (! is_file($barrierPath)) {
    if (microtime(true) >= $deadline) {
        file_put_contents($outputPath, json_encode([
            'success' => false,
            'error' => 'barrier_timeout',
        ], JSON_THROW_ON_ERROR));
        exit(2);
    }

    usleep(10_000);
}

try {
    $exportId = (int) ($payload['report_export_id'] ?? 0);
    app(TemporalApplicationResultExportService::class)->process($exportId);
    $export = ReportExport::query()->findOrFail($exportId);

    file_put_contents($outputPath, json_encode([
        'success' => true,
        'result' => [
            'report_export_id' => (int) $export->getKey(),
        ],
    ], JSON_THROW_ON_ERROR));
    exit(0);
} catch (Throwable $exception) {
    file_put_contents($outputPath, json_encode([
        'success' => false,
        'error' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR));
    exit(1);
}
