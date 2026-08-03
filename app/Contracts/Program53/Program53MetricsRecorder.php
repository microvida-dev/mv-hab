<?php

namespace App\Contracts\Program53;

use App\Data\Program53\Program53OperationalContext;

interface Program53MetricsRecorder
{
    /** @param array<string, bool|int|string> $labels */
    public function record(
        string $metric,
        int|float $value,
        Program53OperationalContext $context,
        array $labels = [],
    ): void;
}
