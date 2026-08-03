<?php

declare(strict_types=1);

namespace App\Services\Visits;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PublicVisitBookingRateLimiter
{
    public function hit(string $email, ?string $ipAddress): void
    {
        $key = $this->key($email, $ipAddress);
        $attempts = max(
            1,
            (int) config('public_visits.rate_limit.attempts', 5),
        );
        $decay = max(
            60,
            (int) config(
                'public_visits.rate_limit.decay_seconds',
                600,
            ),
        );

        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            throw ValidationException::withMessages([
                'email' => 'Foram efetuadas demasiadas tentativas. Aguarde alguns minutos antes de voltar a tentar.',
            ]);
        }

        RateLimiter::hit($key, $decay);
    }

    private function key(string $email, ?string $ipAddress): string
    {
        $payload = Str::lower(trim($email))
            .'|'
            .($ipAddress ?? 'unknown');

        return 'public-visit-booking:'
            .hash_hmac('sha256', $payload, $this->keyMaterial());
    }

    private function keyMaterial(): string
    {
        return hash(
            'sha256',
            (string) config('app.key'),
            true,
        );
    }
}
