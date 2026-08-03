<?php

namespace App\Services\Program53\Resilience;

use App\Data\Program53\Program53Failure;
use App\Enums\Program53FailureCode;
use App\Enums\Program53FailureDisposition;
use App\Exceptions\Program53OperationalException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

final class Program53FailureClassifier
{
    public function classify(Throwable $exception): Program53Failure
    {
        if ($exception instanceof Program53OperationalException) {
            return $this->forCode($exception->failureCode);
        }

        if ($exception instanceof AuthorizationException) {
            return $this->forCode(Program53FailureCode::AuthorizationRevoked);
        }

        if ($exception instanceof ValidationException) {
            $rawCode = $exception->errors()['failure_code'][0] ?? null;
            $code = is_string($rawCode)
                ? $this->legacyCode($rawCode)
                : Program53FailureCode::SourceNotFound;

            return $this->forCode($code);
        }

        if ($exception instanceof QueryException) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            if (
                in_array($sqlState, ['40001', '40P01'], true)
                || in_array($driverCode, [1205, 1213], true)
            ) {
                return $this->forCode(Program53FailureCode::DatabaseDeadlock);
            }

            if (
                str_starts_with($sqlState, '08')
                || in_array($driverCode, [2002, 2006, 2013], true)
            ) {
                return $this->forCode(Program53FailureCode::DatabaseUnavailable);
            }
        }

        $message = mb_strtolower($exception->getMessage());
        $code = match (true) {
            str_contains($message, 'schema'),
            str_contains($message, 'xsd') => Program53FailureCode::SchemaInvalid,
            str_contains($message, 'hash'),
            str_contains($message, 'zip'),
            str_contains($message, 'pacote') => Program53FailureCode::PackageCorrupted,
            str_contains($message, 'document') => Program53FailureCode::DocumentUnavailable,
            str_contains($message, 'storage'),
            str_contains($message, 'ficheiro'),
            str_contains($message, 'destino'),
            str_contains($message, 'staging') => Program53FailureCode::StorageUnavailable,
            default => Program53FailureCode::UnexpectedFailure,
        };

        return $this->forCode($code);
    }

    private function legacyCode(string $code): Program53FailureCode
    {
        return match ($code) {
            'source_stale' => Program53FailureCode::StaleSource,
            'schema_validation_failed' => Program53FailureCode::SchemaInvalid,
            'storage_write_failed' => Program53FailureCode::StorageUnavailable,
            'document_unavailable' => Program53FailureCode::DocumentUnavailable,
            'package_validation_failed' => Program53FailureCode::PackageCorrupted,
            default => Program53FailureCode::tryFrom($code)
                ?? Program53FailureCode::SourceNotFound,
        };
    }

    private function forCode(Program53FailureCode $code): Program53Failure
    {
        $disposition = match ($code) {
            Program53FailureCode::StorageUnavailable,
            Program53FailureCode::DatabaseDeadlock,
            Program53FailureCode::DatabaseUnavailable,
            Program53FailureCode::PackageCorrupted => Program53FailureDisposition::Retryable,
            Program53FailureCode::StaleSource => Program53FailureDisposition::RequiresNewOperation,
            default => Program53FailureDisposition::Terminal,
        };

        return new Program53Failure($code, $disposition);
    }
}
