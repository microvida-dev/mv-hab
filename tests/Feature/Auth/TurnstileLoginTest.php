<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileLoginTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config()->set('turnstile.verify_url', self::VERIFY_URL);
        config()->set('turnstile.timeout_seconds', 5);
    }

    public function test_disabled_turnstile_preserves_current_login_flow(): void
    {
        config()->set('turnstile.enabled', false);
        Http::fake();
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        Http::assertNothingSent();
    }

    public function test_login_page_renders_configured_widget_and_action(): void
    {
        $this->enableTurnstile();

        $this->get('/login')
            ->assertOk()
            ->assertSee('data-sitekey="site-key"', escape: false)
            ->assertSee('data-action="login"', escape: false)
            ->assertSee('data-response-field-name="turnstile_token"', escape: false)
            ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', escape: false);
    }

    public function test_valid_turnstile_token_allows_authentication(): void
    {
        $this->enableTurnstile();
        $this->fakeSuccessfulVerification();
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'turnstile_token' => 'valid-token',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));

        Http::assertSent(static fn (Request $request): bool => $request['response'] === 'valid-token'
            && $request['secret'] === 'secret-key');
    }

    public function test_valid_turnstile_does_not_bypass_invalid_credentials(): void
    {
        $this->enableTurnstile();
        $this->fakeSuccessfulVerification();
        $user = User::factory()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'turnstile_token' => 'valid-token',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        Http::assertSentCount(1);
    }

    public function test_missing_turnstile_token_is_rejected_before_credentials_are_checked(): void
    {
        $this->enableTurnstile();
        Http::fake();
        $user = User::factory()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('turnstile_token');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_provider_rejection_is_audited_without_persisting_token(): void
    {
        $this->enableTurnstile();
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);
        $user = User::factory()->create();
        $token = 'sensitive-turnstile-token';

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'turnstile_token' => $token,
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('turnstile_token');

        $event = AuditEvent::query()
            ->where('event_code', 'human_verification_failed')
            ->firstOrFail();
        $serialized = json_encode($event->toArray(), JSON_THROW_ON_ERROR);
        /** @var array<string, mixed> $metadata */
        $metadata = $event->getAttribute('metadata');

        $this->assertGuest();
        $this->assertStringNotContainsString($token, $serialized);
        $this->assertSame(
            'provider_rejected',
            $metadata['reason_category'] ?? null,
        );
    }

    public function test_existing_credential_rate_limit_is_preserved(): void
    {
        config()->set('turnstile.enabled', false);
        config()->set('auth_security.rate_limits.login_credentials.attempts', 2);
        config()->set('auth_security.rate_limits.login_credentials.decay_seconds', 60);
        Http::fake();
        $user = User::factory()->create();

        foreach (range(1, 2) as $attempt) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password-'.$attempt,
            ])->assertSessionHasErrors('email');
        }

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'auth_rate_limit_exceeded',
        ]);
    }

    private function enableTurnstile(): void
    {
        config()->set('turnstile.enabled', true);
        config()->set('turnstile.site_key', 'site-key');
        config()->set('turnstile.secret_key', 'secret-key');
        config()->set('turnstile.expected_hostname', 'hab.microvida.pt');
        config()->set('turnstile.action', 'login');
    }

    private function fakeSuccessfulVerification(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
                'hostname' => 'hab.microvida.pt',
                'action' => 'login',
                'error-codes' => [],
            ]),
        ]);
    }
}
