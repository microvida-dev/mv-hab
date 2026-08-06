<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Contracts\Security\HumanVerificationVerifier;
use App\Enums\Security\HumanVerificationContext;
use App\Services\Security\AuthAbuseAuditService;
use App\Services\Security\AuthRateLimitService;
use App\Services\Security\LoginHistoryService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'turnstile_token' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $result = app(HumanVerificationVerifier::class)->verify(
                    HumanVerificationContext::Login,
                    $this->string('turnstile_token')->toString(),
                    $this->ip(),
                );

                if ($result->successful) {
                    return;
                }

                if ($result->failureReason !== null) {
                    app(AuthAbuseAuditService::class)
                        ->recordHumanVerificationFailure(
                            $this,
                            HumanVerificationContext::Login,
                            $result->failureReason,
                        );
                }

                $validator->errors()->add(
                    'turnstile_token',
                    trans('auth.human_verification_failed'),
                );
            },
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(
            $this->only('email', 'password'),
            $this->boolean('remember'),
        )) {
            $rateLimits = app(AuthRateLimitService::class);

            RateLimiter::hit(
                $this->throttleKey(),
                $rateLimits->loginCredentialDecaySeconds(),
            );

            app(LoginHistoryService::class)->recordFailed(
                (string) $this->string('email'),
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $rateLimits = app(AuthRateLimitService::class);

        if (! RateLimiter::tooManyAttempts(
            $this->throttleKey(),
            $rateLimits->loginCredentialAttempts(),
        )) {
            return;
        }

        event(new Lockout($this));
        app(AuthAbuseAuditService::class)->recordRateLimitExceeded(
            $this,
            'login_credentials',
        );

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return app(AuthRateLimitService::class)
            ->loginCredentialKey($this);
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        $this->merge([
            'email' => is_string($email)
                ? Str::lower(trim($email))
                : $email,
            'turnstile_token' => $this->input(
                'turnstile_token',
                $this->input('cf-turnstile-response'),
            ),
        ]);
    }
}
