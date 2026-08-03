<?php

namespace App\Services\Security;

use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

final class Program53RateLimitService
{
    public const EXPORT_PREVIEW = 'export_preview';

    public const EXPORT_REQUEST = 'export_request';

    public const EXPORT_DOWNLOAD = 'export_download';

    public const BATCH_SEAL = 'batch_seal';

    public const BATCH_PUBLISH = 'batch_publish';

    public const REVALIDATION_SEAL = 'revalidation_seal';

    public function __construct(private readonly Program53RateLimitAuditService $audit) {}

    /** @return list<Limit> */
    public function limits(Request $request, string $operation): array
    {
        $user = $request->user();

        if (! $user instanceof User || $user->municipality_id === null) {
            return [
                (new Limit(
                    $this->key(0, 0, $operation, 'unresolved', 'municipality'),
                    0,
                    60,
                ))
                    ->response(fn (Request $blockedRequest, array $headers): Response => $this->response(
                        $blockedRequest,
                        $headers,
                        $operation,
                        'unresolved',
                    )),
            ];
        }

        $profile = $this->usesSensitiveProfile($request, $operation) ? 'sensitive' : 'normal';
        $configuration = $this->configuration($operation, $profile);
        $resource = $this->resourceIdentifier($request);

        return [
            $this->limit(
                $operation,
                'user',
                $this->key(
                    (int) $user->id,
                    (int) $user->municipality_id,
                    $operation.':'.$profile,
                    $resource,
                    'user',
                ),
                $configuration['user'],
            ),
            $this->limit(
                $operation,
                'municipality',
                $this->key(
                    (int) $user->id,
                    (int) $user->municipality_id,
                    $operation.':'.$profile,
                    $resource,
                    'municipality',
                ),
                $configuration['municipality'],
            ),
        ];
    }

    public function key(
        int $actorId,
        int $municipalityId,
        string $operation,
        string|int|null $resourceIdentifier,
        string $dimension,
    ): string {
        if (! in_array($dimension, ['user', 'municipality'], true)) {
            throw new LogicException('Dimensão de rate limit inválida.');
        }

        $technicalParts = [
            'v1',
            $dimension,
            $dimension === 'user' ? (string) $actorId : 'aggregate',
            (string) $municipalityId,
            $operation,
            $dimension === 'user'
                ? (string) ($resourceIdentifier ?? 'none')
                : 'all-resources',
        ];

        return 'program53:'.hash('sha256', implode('|', $technicalParts));
    }

    public function usesSensitiveProfile(Request $request, string $operation): bool
    {
        if (! in_array($operation, [self::EXPORT_PREVIEW, self::EXPORT_REQUEST, self::EXPORT_DOWNLOAD], true)) {
            return false;
        }

        $reportExport = $request->route('reportExport');

        if ($reportExport instanceof ReportExport) {
            return $reportExport->sensitive_fields_included
                || $reportExport->document_files_requested;
        }

        return $request->boolean('include_sensitive')
            || $request->boolean('include_document_files');
    }

    /**
     * @return array{
     *     user: array{max_attempts: int, decay_seconds: int},
     *     municipality: array{max_attempts: int, decay_seconds: int}
     * }
     */
    public function configuration(string $operation, string $profile = 'normal'): array
    {
        $configuration = config("mvhab.rate_limits.program53.{$operation}.{$profile}");

        if (! is_array($configuration) && $profile === 'sensitive') {
            throw new LogicException("Configuração sensível em falta para {$operation}.");
        }

        if (! is_array($configuration)) {
            throw new LogicException("Configuração de rate limit em falta para {$operation}.");
        }

        return [
            'user' => $this->dimensionConfiguration($configuration, 'user', $operation, $profile),
            'municipality' => $this->dimensionConfiguration(
                $configuration,
                'municipality',
                $operation,
                $profile,
            ),
        ];
    }

    /** @param array{max_attempts: int, decay_seconds: int} $configuration */
    private function limit(
        string $operation,
        string $dimension,
        string $key,
        array $configuration,
    ): Limit {
        return (new Limit(
            $key,
            $configuration['max_attempts'],
            $configuration['decay_seconds'],
        ))->response(fn (Request $blockedRequest, array $headers): Response => $this->response(
            $blockedRequest,
            $headers,
            $operation,
            $dimension,
        ));
    }

    /** @param array<string, string|int> $headers */
    private function response(
        Request $request,
        array $headers,
        string $operation,
        string $dimension,
    ): Response {
        $this->audit->record($request, $operation, $dimension);

        $message = 'O limite temporário desta operação foi atingido. Tente novamente após o intervalo indicado.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        return response($message, Response::HTTP_TOO_MANY_REQUESTS, $headers)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array{max_attempts: int, decay_seconds: int}
     */
    private function dimensionConfiguration(
        array $configuration,
        string $dimension,
        string $operation,
        string $profile,
    ): array {
        $values = $configuration[$dimension] ?? null;

        if (! is_array($values)) {
            throw new LogicException("Dimensão {$dimension} inválida para {$operation}/{$profile}.");
        }

        $maxAttempts = $values['max_attempts'] ?? null;
        $decaySeconds = $values['decay_seconds'] ?? null;

        if (! is_int($maxAttempts) || $maxAttempts < 1 || ! is_int($decaySeconds) || $decaySeconds < 1) {
            throw new LogicException("Valores de rate limit inválidos para {$operation}/{$profile}/{$dimension}.");
        }

        return [
            'max_attempts' => $maxAttempts,
            'decay_seconds' => $decaySeconds,
        ];
    }

    private function resourceIdentifier(Request $request): string|int|null
    {
        foreach (['reportExport', 'applicationReviewBatch', 'correctionRequest', 'contest'] as $parameter) {
            $value = $request->route($parameter);

            if ($value instanceof Model) {
                return (string) $value->getRouteKey();
            }

            if (is_string($value)) {
                return $value;
            }
        }

        foreach (['batch_public_id', 'target_batch_public_id', 'contest_id'] as $field) {
            $value = $request->input($field);

            if (is_string($value) || is_int($value)) {
                return $value;
            }
        }

        return null;
    }
}
