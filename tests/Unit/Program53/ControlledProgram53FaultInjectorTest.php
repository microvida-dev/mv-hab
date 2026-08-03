<?php

namespace Tests\Unit\Program53;

use App\Data\Program53\Program53OperationalContext;
use App\Enums\Program53FailureCode;
use App\Exceptions\Program53OperationalException;
use App\Services\Program53\Resilience\ControlledProgram53FaultInjector;
use Tests\TestCase;

final class ControlledProgram53FaultInjectorTest extends TestCase
{
    public function test_controlled_failure_is_injected_once_in_testing(): void
    {
        $injector = new ControlledProgram53FaultInjector([
            'after_snapshot_checksum' => Program53FailureCode::StorageUnavailable,
        ]);
        $context = new Program53OperationalContext('operation-53');

        try {
            $injector->checkpoint('after_snapshot_checksum', $context);
            $this->fail('O checkpoint deveria injetar a falha configurada.');
        } catch (Program53OperationalException $exception) {
            $this->assertSame(
                Program53FailureCode::StorageUnavailable,
                $exception->failureCode,
            );
        }

        $injector->checkpoint('after_snapshot_checksum', $context);
        $this->addToAssertionCount(1);
    }
}
