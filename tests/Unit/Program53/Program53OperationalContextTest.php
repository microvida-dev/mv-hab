<?php

namespace Tests\Unit\Program53;

use App\Data\Program53\Program53OperationalContext;
use PHPUnit\Framework\TestCase;

final class Program53OperationalContextTest extends TestCase
{
    public function test_context_contains_only_technical_identifiers_and_can_change_stage(): void
    {
        $context = new Program53OperationalContext(
            operationId: 'operation-53',
            requestId: 'request-53',
            correlationId: 'correlation-53',
            municipalityId: 4,
            contestId: 8,
            exportId: 12,
            attempt: 2,
            stage: 'snapshot',
        );

        $packaging = $context->withStage('packaging');

        $this->assertSame('snapshot', $context->stage);
        $this->assertSame('packaging', $packaging->stage);
        $this->assertSame(2, $packaging->attempt);
        $this->assertSame(4, $packaging->municipalityId);
        $this->assertArrayNotHasKey('email', $packaging->toArray());
        $this->assertArrayNotHasKey('path', $packaging->toArray());
    }
}
