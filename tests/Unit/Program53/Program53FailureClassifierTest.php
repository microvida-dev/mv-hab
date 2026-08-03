<?php

namespace Tests\Unit\Program53;

use App\Enums\Program53FailureCode;
use App\Enums\Program53FailureDisposition;
use App\Exceptions\Program53OperationalException;
use App\Services\Program53\Resilience\Program53FailureClassifier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class Program53FailureClassifierTest extends TestCase
{
    #[DataProvider('failures')]
    public function test_failure_classification_is_typed_and_minimized(
        string $scenario,
        Program53FailureCode $code,
        Program53FailureDisposition $disposition,
    ): void {
        $exception = match ($scenario) {
            'storage' => new Program53OperationalException(
                Program53FailureCode::StorageUnavailable,
            ),
            'stale' => ValidationException::withMessages([
                'failure_code' => 'source_stale',
            ]),
            'authorization' => new AuthorizationException(
                'candidate@example.test',
            ),
            default => throw new LogicException('Cenário de teste desconhecido.'),
        };
        $failure = app(Program53FailureClassifier::class)->classify($exception);

        $this->assertSame($code, $failure->code);
        $this->assertSame($disposition, $failure->disposition);
        $this->assertStringNotContainsString('/', $failure->safeMessage());
        $this->assertStringNotContainsString('@', $failure->safeMessage());
    }

    /** @return iterable<string, array{string, Program53FailureCode, Program53FailureDisposition}> */
    public static function failures(): iterable
    {
        yield 'storage retryable' => [
            'storage',
            Program53FailureCode::StorageUnavailable,
            Program53FailureDisposition::Retryable,
        ];
        yield 'stale source requires new operation' => [
            'stale',
            Program53FailureCode::StaleSource,
            Program53FailureDisposition::RequiresNewOperation,
        ];
        yield 'authorization terminal' => [
            'authorization',
            Program53FailureCode::AuthorizationRevoked,
            Program53FailureDisposition::Terminal,
        ];
    }
}
