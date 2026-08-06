<?php

declare(strict_types=1);

namespace App\Data\Security;

final readonly class HumanVerificationConfiguration
{
    public function __construct(
        public bool $enabled,
        public ?string $siteKey,
        public ?string $secretKey,
        public ?string $verifyUrl,
        public int $timeoutSeconds,
        public ?string $expectedHostname,
        public string $expectedAction,
    ) {}

    public function isComplete(): bool
    {
        return $this->filled($this->siteKey)
            && $this->filled($this->secretKey)
            && $this->validVerifyUrl()
            && $this->filled($this->expectedHostname)
            && preg_match('/^[a-z0-9_-]{1,32}$/i', $this->expectedAction) === 1
            && $this->timeoutSeconds > 0;
    }

    private function validVerifyUrl(): bool
    {
        if (! $this->filled($this->verifyUrl)) {
            return false;
        }

        $scheme = parse_url((string) $this->verifyUrl, PHP_URL_SCHEME);

        return is_string($scheme) && strtolower($scheme) === 'https';
    }

    private function filled(?string $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
