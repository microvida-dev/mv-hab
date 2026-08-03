<?php

namespace App\Services\Program53\Resilience;

use App\Contracts\Program53\Program53FaultInjector;
use App\Data\Program53\Program53OperationalContext;
use App\Enums\Program53FailureCode;
use App\Exceptions\Program53OperationalException;
use RuntimeException;

/**
 * Injetor disponível apenas por construção explícita em testes internos.
 */
final class ControlledProgram53FaultInjector implements Program53FaultInjector
{
    /** @var array<string, int> */
    private array $remaining;

    /**
     * @param  array<string, Program53FailureCode>  $failures
     * @param  positive-int  $times
     */
    public function __construct(
        private readonly array $failures,
        int $times = 1,
    ) {
        if (! app()->environment('testing')) {
            throw new RuntimeException(
                'A injeção controlada de falhas só está disponível em testes.',
            );
        }

        $this->remaining = array_fill_keys(
            array_keys($failures),
            max(1, $times),
        );
    }

    public function checkpoint(
        string $checkpoint,
        Program53OperationalContext $context,
    ): void {
        unset($context);

        $remaining = $this->remaining[$checkpoint] ?? 0;
        $code = $this->failures[$checkpoint] ?? null;
        if ($remaining < 1 || ! $code instanceof Program53FailureCode) {
            return;
        }

        $this->remaining[$checkpoint] = $remaining - 1;

        throw new Program53OperationalException($code);
    }
}
