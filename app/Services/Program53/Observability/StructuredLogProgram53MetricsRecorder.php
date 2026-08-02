<?php

namespace App\Services\Program53\Observability;

use App\Contracts\Program53\Program53MetricsRecorder;
use App\Data\Program53\Program53OperationalContext;
use Illuminate\Support\Facades\Log;

final class StructuredLogProgram53MetricsRecorder implements Program53MetricsRecorder
{
    public function __construct(
        private readonly Program53ContextRedactor $redactor,
    ) {}

    /** @param array<string, bool|int|string> $labels */
    public function record(
        string $metric,
        int|float $value,
        Program53OperationalContext $context,
        array $labels = [],
    ): void {
        if (preg_match('/^[a-z][a-z0-9_.]{0,119}$/', $metric) !== 1) {
            return;
        }

        Log::info('program53.metric', $this->redactor->redact([
            'event' => 'program53_metric',
            'metric' => $metric,
            'value' => $value,
            ...$context->toArray(),
            'labels' => $labels,
        ]));
    }
}
