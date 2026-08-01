<?php

declare(strict_types=1);

use App\Enums\CorrectionResponseReviewResult;
use App\Models\ApplicationReviewBatch;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Administrative\CorrectionResolutionService;
use App\Services\Administrative\CorrectionRevalidationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$application = require dirname(__DIR__, 3).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

[$script, $operation, $payloadPath, $barrierPath, $readyPath, $outputPath] = $argv;
$payload = json_decode(
    (string) file_get_contents($payloadPath),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
touch($readyPath);

$deadline = microtime(true) + 20;

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
    $actor = User::query()->findOrFail((int) $payload['actor_id']);
    Auth::login($actor);

    $result = match ($operation) {
        'start' => (function () use ($payload, $actor): array {
            $request = app(CorrectionRevalidationService::class)->start(
                CorrectionRequest::query()->findOrFail(
                    (int) $payload['correction_request_id'],
                ),
                $actor,
            );

            return ['correction_request_id' => (int) $request->id];
        })(),
        'decide' => (function () use ($payload, $actor): array {
            $response = app(CorrectionRevalidationService::class)->decide(
                request: CorrectionRequest::query()->findOrFail(
                    (int) $payload['correction_request_id'],
                ),
                response: CorrectionResponse::query()->findOrFail(
                    (int) $payload['correction_response_id'],
                ),
                result: CorrectionResponseReviewResult::from(
                    (string) $payload['result'],
                ),
                reviewNotes: (string) $payload['review_notes'],
                sourceFingerprint: (string) $payload['source_fingerprint'],
                expectedDecisionToken: null,
                actor: $actor,
            );

            return ['correction_response_id' => (int) $response->id];
        })(),
        'seal' => (function () use ($payload, $actor): array {
            $batch = app(CorrectionResolutionService::class)->seal(
                request: CorrectionRequest::query()->findOrFail(
                    (int) $payload['correction_request_id'],
                ),
                actor: $actor,
                reason: (string) $payload['reason'],
                previewToken: (string) $payload['preview_token'],
            );

            return ['application_review_batch_id' => (int) $batch->id];
        })(),
        'publish' => (function () use ($payload, $actor): array {
            $publication = app(ApplicationReviewPublicationService::class)
                ->publish(
                    ApplicationReviewBatch::query()->findOrFail(
                        (int) $payload['application_review_batch_id'],
                    ),
                    $actor,
                    [
                        'reason' => (string) $payload['reason'],
                        'preview_token' => (string) $payload['preview_token'],
                    ],
                );

            return ['application_review_publication_id' => (int) $publication->id];
        })(),
        default => throw new RuntimeException('Operação de concorrência desconhecida.'),
    };

    file_put_contents($outputPath, json_encode([
        'success' => true,
        'result' => $result,
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
