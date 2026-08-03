<?php

namespace App\Data\Program53;

use App\Enums\Program53HealthSeverity;

final readonly class Program53HealthFinding
{
    /** @param array<string, bool|int|string|null> $context */
    public function __construct(
        public string $code,
        public Program53HealthSeverity $severity,
        public string $message,
        public array $context = [],
    ) {}

    /** @return array{code: string, severity: string, message: string, context: array<string, bool|int|string|null>} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
