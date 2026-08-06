<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AuthRateLimitService
{
    public const LOGIN_SUBMISSION = 'login_submission';

    public const REGISTRATION = 'registration';

    public const PASSWORD_RESET = 'password_reset';

    public const VERIFICATION_RESEND = 'verification_resend';

    /** @var array<string, string> */
    public const LIMITERS = [
        'auth.login-submission' => self::LOGIN_SUBMISSION,
        'auth.registration' => self::REGISTRATION,
        'auth.password-reset' => self::PASSWORD_RESET,
        'auth.verification-resend' => self::VERIFICATION_RESEND,
    ];

    public function __construct(
        private readonly AuthAbuseAuditService $audit,
    ) {}

    /**
     * @return list<Limit>
     */
    public function limits(Request $request, string $operation): array
    {
        return match ($operation) {
            self::LOGIN_SUBMISSION => [
                $this->limit(
                    operation: $operation,
                    attempts: $this->integer('login_submission.attempts', 20),
                    decayMinutes: $this->integer('login_submission.decay_minutes', 1),
                    key: 'ip:'.$this->ipHash($request),
                ),
            ],
            self::REGISTRATION => [
                $this->limit(
                    operation: $operation,
                    attempts: $this->integer('registration.ip_attempts', 5),
                    decayMinutes: $this->integer('registration.ip_decay_minutes', 10),
                    key: 'ip:'.$this->ipHash($request),
                ),
                $this->limit(
                    operation: $operation,
                    attempts: $this->integer('registration.email_attempts', 3),
                    decayMinutes: $this->integer('registration.email_decay_minutes', 60),
                    key: 'email:'.$this->emailHash($request),
                ),
            ],
            self::PASSWORD_RESET => [
                $this->limit(
                    operation: $operation,
                    attempts: $this->integer('password_reset.ip_attempts', 5),
                    decayMinutes: $this->integer('password_reset.ip_decay_minutes', 10),
                    key: 'ip:'.$this->ipHash($request),
                ),
                $this->limit(
                    operation: $operation,
                    attempts: $this->integer('password_reset.email_attempts', 3),
                    decayMinutes: $this->integer('password_reset.email_decay_minutes', 10),
                    key: 'email:'.$this->emailHash($request),
                ),
            ],
            self::VERIFICATION_RESEND => [
                $this->limit(
                    operation: $operation,
                    attempts: $this->integer('verification_resend.attempts', 6),
                    decayMinutes: $this->integer('verification_resend.decay_minutes', 1),
                    key: $request->user() !== null
                        ? 'user:'.(string) $request->user()->getAuthIdentifier()
                        : 'ip:'.$this->ipHash($request),
                ),
            ],
            default => [],
        };
    }

    public function loginCredentialKey(Request $request): string
    {
        return 'auth-login-credentials:'
            .$this->emailHash($request)
            .'|'.$this->ipHash($request);
    }

    public function loginCredentialAttempts(): int
    {
        return $this->integer('login_credentials.attempts', 5);
    }

    public function loginCredentialDecaySeconds(): int
    {
        return $this->integer('login_credentials.decay_seconds', 60);
    }

    private function limit(
        string $operation,
        int $attempts,
        int $decayMinutes,
        string $key,
    ): Limit {
        return Limit::perMinute(
            max(1, $attempts),
            max(1, $decayMinutes),
        )
            ->by($key)
            ->response(fn (Request $limitedRequest, array $headers): Response => $this->tooManyAttemptsResponse(
                $limitedRequest,
                $operation,
                $headers,
            ));
    }

    /**
     * @param  array<string, int|string>  $headers
     */
    private function tooManyAttemptsResponse(
        Request $request,
        string $operation,
        array $headers,
    ): JsonResponse|RedirectResponse {
        $this->audit->recordRateLimitExceeded($request, $operation);

        $retryAfter = max(1, (int) ($headers['Retry-After'] ?? 60));
        $message = trans('auth.rate_limited', [
            'seconds' => $retryAfter,
            'minutes' => (int) ceil($retryAfter / 60),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        return back()
            ->withInput($request->except([
                'password',
                'password_confirmation',
                'turnstile_token',
                'cf-turnstile-response',
            ]))
            ->withErrors(['email' => $message])
            ->withHeaders($headers);
    }

    private function integer(string $key, int $default): int
    {
        return max(1, (int) config('auth_security.rate_limits.'.$key, $default));
    }

    private function emailHash(Request $request): string
    {
        $email = $request->input('email');
        $normalized = is_string($email)
            ? Str::lower(trim($email))
            : '';

        return hash('sha256', $normalized !== '' ? $normalized : 'missing');
    }

    private function ipHash(Request $request): string
    {
        return hash('sha256', (string) ($request->ip() ?? 'unknown'));
    }
}
