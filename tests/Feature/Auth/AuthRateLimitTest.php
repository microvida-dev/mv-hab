<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Services\Security\AuthRateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_rate_limiters_are_registered(): void
    {
        foreach (array_keys(AuthRateLimitService::LIMITERS) as $name) {
            $this->assertNotNull(
                RateLimiter::limiter($name),
                sprintf('Limiter %s should be registered.', $name),
            );
        }
    }

    public function test_login_submission_limiter_blocks_excess_requests_without_flashing_secrets(): void
    {
        config()->set('auth_security.rate_limits.login_submission.attempts', 1);
        config()->set('auth_security.rate_limits.login_submission.decay_minutes', 1);

        $this->from('/login')->post('/login', [
            'email' => 'first@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $response = $this->from('/login')->post('/login', [
            'email' => 'second@example.test',
            'password' => 'sensitive-password',
            'turnstile_token' => 'sensitive-token',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        /** @var array<string, mixed> $oldInput */
        $oldInput = session()->getOldInput();

        $this->assertArrayNotHasKey('password', $oldInput);
        $this->assertArrayNotHasKey('turnstile_token', $oldInput);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'auth_rate_limit_exceeded',
        ]);
    }

    public function test_sensitive_auth_routes_use_expected_named_limiters(): void
    {
        $expectations = [
            ['POST', 'login', 'throttle:auth.login-submission'],
            ['POST', 'register', 'throttle:auth.registration'],
            ['POST', 'forgot-password', 'throttle:auth.password-reset'],
            ['POST', 'email/verification-notification', 'throttle:auth.verification-resend'],
        ];

        foreach ($expectations as [$method, $uri, $middleware]) {
            $route = collect(Route::getRoutes()->getRoutes())
                ->first(static fn ($route): bool => $route->uri() === $uri
                    && in_array($method, $route->methods(), true));

            $this->assertNotNull($route, sprintf('%s %s route missing.', $method, $uri));
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
    }
}
