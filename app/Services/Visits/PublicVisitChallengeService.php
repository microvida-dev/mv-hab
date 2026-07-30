<?php

declare(strict_types=1);

namespace App\Services\Visits;

use Illuminate\Support\Facades\Http;

final class PublicVisitChallengeService
{
    public function enabled(): bool
    {
        return (bool) config('public_visits.turnstile.enabled', false);
    }

    public function siteKey(): ?string
    {
        $siteKey = config('public_visits.turnstile.site_key');

        return is_string($siteKey) && $siteKey !== ''
            ? $siteKey
            : null;
    }

    public function verify(?string $token, ?string $ipAddress): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        $secret = config('public_visits.turnstile.secret_key');
        $verifyUrl = config('public_visits.turnstile.verify_url');

        if (! is_string($secret)
            || $secret === ''
            || ! is_string($verifyUrl)
            || $verifyUrl === ''
            || ! is_string($token)
            || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config(
                    'public_visits.turnstile.timeout_seconds',
                    5,
                ))
                ->post($verifyUrl, [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);
        } catch (\Throwable) {
            return false;
        }

        return $response->successful()
            && $response->json('success') === true;
    }
}
