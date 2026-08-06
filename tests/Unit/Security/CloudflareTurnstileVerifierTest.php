<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Contracts\Security\HumanVerificationVerifier;
use App\Enums\Security\HumanVerificationContext;
use App\Enums\Security\HumanVerificationFailureReason;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CloudflareTurnstileVerifierTest extends TestCase
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config()->set('turnstile.enabled', true);
        config()->set('turnstile.site_key', 'site-key');
        config()->set('turnstile.secret_key', 'secret-key');
        config()->set('turnstile.expected_hostname', 'hab.microvida.pt');
        config()->set('turnstile.action', 'login');
        config()->set('turnstile.verify_url', self::VERIFY_URL);
        config()->set('turnstile.timeout_seconds', 5);
    }

    public function test_disabled_context_succeeds_without_external_request(): void
    {
        config()->set('turnstile.enabled', false);
        Http::fake();

        $result = $this->verifier()->verify(
            HumanVerificationContext::Login,
            null,
            '127.0.0.1',
        );

        $this->assertTrue($result->successful);
        $this->assertNull($result->failureReason);
        Http::assertNothingSent();
    }

    public function test_enabled_context_fails_closed_when_configuration_is_incomplete(): void
    {
        config()->set('turnstile.expected_hostname', null);
        Http::fake();

        $result = $this->verifier()->verify(
            HumanVerificationContext::Login,
            'token',
            '127.0.0.1',
        );

        $this->assertFalse($result->successful);
        $this->assertSame(
            HumanVerificationFailureReason::ConfigurationIncomplete,
            $result->failureReason,
        );
        Http::assertNothingSent();
    }

    public function test_valid_response_is_accepted_and_request_uses_safe_fields(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
                'hostname' => 'hab.microvida.pt',
                'action' => 'login',
                'error-codes' => [],
            ]),
        ]);

        $result = $this->verifier()->verify(
            HumanVerificationContext::Login,
            'valid-token',
            '203.0.113.20',
        );

        $this->assertTrue($result->successful);
        $this->assertSame('hab.microvida.pt', $result->hostname);
        $this->assertSame('login', $result->action);

        Http::assertSent(static function (Request $request): bool {
            $idempotencyKey = $request['idempotency_key'];

            return $request->url() === self::VERIFY_URL
                && $request['secret'] === 'secret-key'
                && $request['response'] === 'valid-token'
                && $request['remoteip'] === '203.0.113.20'
                && is_string($idempotencyKey)
                && Str::isUuid($idempotencyKey);
        });
    }

    public function test_provider_rejection_is_returned_without_exposing_token(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $result = $this->verifier()->verify(
            HumanVerificationContext::Login,
            'rejected-token',
            '127.0.0.1',
        );

        $this->assertFalse($result->successful);
        $this->assertSame(
            HumanVerificationFailureReason::ProviderRejected,
            $result->failureReason,
        );
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    public function test_hostname_mismatch_is_rejected(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
                'hostname' => 'example.test',
                'action' => 'login',
            ]),
        ]);

        $result = $this->verifier()->verify(
            HumanVerificationContext::Login,
            'token',
            '127.0.0.1',
        );

        $this->assertFalse($result->successful);
        $this->assertSame(
            HumanVerificationFailureReason::HostnameMismatch,
            $result->failureReason,
        );
    }

    public function test_action_mismatch_is_rejected(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
                'hostname' => 'hab.microvida.pt',
                'action' => 'register',
            ]),
        ]);

        $result = $this->verifier()->verify(
            HumanVerificationContext::Login,
            'token',
            '127.0.0.1',
        );

        $this->assertFalse($result->successful);
        $this->assertSame(
            HumanVerificationFailureReason::ActionMismatch,
            $result->failureReason,
        );
    }

    public function test_transport_failure_is_rejected(): void
    {
        Http::fake(static function (): never {
            throw new ConnectionException('Turnstile unavailable');
        });

        $result = $this->verifier()->verify(
            HumanVerificationContext::Login,
            'token',
            '127.0.0.1',
        );

        $this->assertFalse($result->successful);
        $this->assertSame(
            HumanVerificationFailureReason::TransportFailure,
            $result->failureReason,
        );
    }

    private function verifier(): HumanVerificationVerifier
    {
        return app(HumanVerificationVerifier::class);
    }
}
