<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Contracts\Security\HumanVerificationVerifier;
use App\Data\Security\HumanVerificationConfiguration;
use App\Data\Security\HumanVerificationResult;
use App\Enums\Security\HumanVerificationContext;
use App\Enums\Security\HumanVerificationFailureReason;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class CloudflareTurnstileVerifier implements HumanVerificationVerifier
{
    public function __construct(
        private readonly HumanVerificationConfigurationResolver $configuration,
    ) {}

    public function enabled(HumanVerificationContext $context): bool
    {
        return $this->configuration->resolve($context)->enabled;
    }

    public function siteKey(HumanVerificationContext $context): ?string
    {
        return $this->configuration->resolve($context)->siteKey;
    }

    public function action(HumanVerificationContext $context): string
    {
        return $this->configuration->resolve($context)->expectedAction;
    }

    public function verify(
        HumanVerificationContext $context,
        ?string $token,
        ?string $ipAddress,
    ): HumanVerificationResult {
        $configuration = $this->configuration->resolve($context);

        if (! $configuration->enabled) {
            return HumanVerificationResult::success();
        }

        if (! $configuration->isComplete()) {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::ConfigurationIncomplete,
            );
        }

        if (! is_string($token) || trim($token) === '') {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::TokenMissing,
            );
        }

        $token = trim($token);

        if (strlen($token) > 2048) {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::TokenInvalid,
            );
        }

        $payload = [
            'secret' => $configuration->secretKey,
            'response' => $token,
            'idempotency_key' => Str::uuid()->toString(),
        ];

        if (is_string($ipAddress) && $ipAddress !== '') {
            $payload['remoteip'] = $ipAddress;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(min(3, $configuration->timeoutSeconds))
                ->timeout($configuration->timeoutSeconds)
                ->post((string) $configuration->verifyUrl, $payload);
        } catch (ConnectionException) {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::TransportFailure,
            );
        }

        return $this->result($response, $configuration);
    }

    private function result(
        Response $response,
        HumanVerificationConfiguration $configuration,
    ): HumanVerificationResult {
        if (! $response->successful()) {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::HttpFailure,
            );
        }

        $hostname = $this->nullableString($response->json('hostname'));
        $action = $this->nullableString($response->json('action'));
        $errorCodes = $this->errorCodes($response->json('error-codes', []));

        if ($response->json('success') !== true) {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::ProviderRejected,
                hostname: $hostname,
                action: $action,
                errorCodes: $errorCodes,
            );
        }

        if (! hash_equals(
            strtolower((string) $configuration->expectedHostname),
            strtolower((string) $hostname),
        )) {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::HostnameMismatch,
                hostname: $hostname,
                action: $action,
                errorCodes: $errorCodes,
            );
        }

        if (! hash_equals($configuration->expectedAction, (string) $action)) {
            return HumanVerificationResult::failure(
                HumanVerificationFailureReason::ActionMismatch,
                hostname: $hostname,
                action: $action,
                errorCodes: $errorCodes,
            );
        }

        return HumanVerificationResult::success($hostname, $action);
    }

    /**
     * @return list<string>
     */
    private function errorCodes(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $code): bool => is_string($code) && $code !== '',
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
