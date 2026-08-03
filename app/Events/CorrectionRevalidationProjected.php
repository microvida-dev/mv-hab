<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CorrectionRevalidationProjected implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $municipalityId,
        public readonly int $contestId,
        public readonly int $administrativeProcessId,
        public readonly int $applicationId,
        public readonly int $correctionRequestId,
        public readonly int $publicationResultId,
        public readonly string $outcome,
        public readonly int $projectedBy,
        public readonly string $projectedAt,
    ) {}
}
