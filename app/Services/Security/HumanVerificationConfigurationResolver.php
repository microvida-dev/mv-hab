<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Data\Security\HumanVerificationConfiguration;
use App\Enums\Security\HumanVerificationContext;

final class HumanVerificationConfigurationResolver
{
    public function resolve(
        HumanVerificationContext $context,
    ): HumanVerificationConfiguration {
        $prefix = $context->configPrefix();

        return new HumanVerificationConfiguration(
            enabled: (bool) config($prefix.'.enabled', false),
            siteKey: $this->nullableString(config($prefix.'.site_key')),
            secretKey: $this->nullableString(config($prefix.'.secret_key')),
            verifyUrl: $this->nullableString(config(
                $prefix.'.verify_url',
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            )),
            timeoutSeconds: min(
                10,
                max(
                    1,
                    (int) config($prefix.'.timeout_seconds', 5),
                ),
            ),
            expectedHostname: $this->nullableString(config(
                $prefix.'.expected_hostname',
            )),
            expectedAction: $this->string(
                config($prefix.'.action'),
                $context->defaultAction(),
            ),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function string(mixed $value, string $default): string
    {
        return $this->nullableString($value) ?? $default;
    }
}
