<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Services\Security\AuthRateLimitService;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuthRateLimitServiceTest extends TestCase
{
    public function test_login_credential_limit_preserves_five_attempts_per_minute(): void
    {
        $service = app(AuthRateLimitService::class);
        $request = Request::create('/login', 'POST', [
            'email' => 'Candidate@Example.test',
        ], server: ['REMOTE_ADDR' => '203.0.113.10']);

        $key = $service->loginCredentialKey($request);

        $this->assertSame(5, $service->loginCredentialAttempts());
        $this->assertSame(60, $service->loginCredentialDecaySeconds());
        $this->assertStringNotContainsString('candidate@example.test', $key);
        $this->assertStringNotContainsString('203.0.113.10', $key);
    }

    public function test_registration_limits_use_hashed_email_and_ip_dimensions(): void
    {
        $service = app(AuthRateLimitService::class);
        $request = Request::create('/register', 'POST', [
            'email' => 'candidate@example.test',
        ], server: ['REMOTE_ADDR' => '203.0.113.10']);

        $limits = $service->limits(
            $request,
            AuthRateLimitService::REGISTRATION,
        );

        $this->assertCount(2, $limits);
        $this->assertSame(5, $limits[0]->maxAttempts);
        $this->assertSame(600, $limits[0]->decaySeconds);
        $this->assertSame(3, $limits[1]->maxAttempts);
        $this->assertSame(3600, $limits[1]->decaySeconds);

        foreach ($limits as $limit) {
            $this->assertStringNotContainsString(
                'candidate@example.test',
                (string) $limit->key,
            );
            $this->assertStringNotContainsString(
                '203.0.113.10',
                (string) $limit->key,
            );
        }
    }
}
