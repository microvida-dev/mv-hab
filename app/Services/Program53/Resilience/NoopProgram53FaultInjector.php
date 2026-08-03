<?php

namespace App\Services\Program53\Resilience;

use App\Contracts\Program53\Program53FaultInjector;
use App\Data\Program53\Program53OperationalContext;

final class NoopProgram53FaultInjector implements Program53FaultInjector
{
    public function checkpoint(
        string $checkpoint,
        Program53OperationalContext $context,
    ): void {
        unset($checkpoint, $context);
    }
}
