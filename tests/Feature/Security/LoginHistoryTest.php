<?php

namespace Tests\Feature\Security;

use App\Enums\AccessLogType;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_login_success_failure_and_logout_are_logged_without_raw_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'qa32-login@example.test',
            'password' => 'valid-secret',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-secret',
        ])->assertSessionHasErrors();

        $this->assertDatabaseHas('access_logs', [
            'access_type' => AccessLogType::FailedLogin->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'login_failed',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'valid-secret',
        ])->assertRedirect();

        $this->assertDatabaseHas('access_logs', [
            'user_id' => $user->id,
            'access_type' => AccessLogType::Login->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'login_success',
            'user_id' => $user->id,
        ]);

        $this->post('/logout')->assertRedirect('/');
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'logout',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('access_logs', [
            'metadata->password' => 'wrong-secret',
        ]);
    }

    public function test_failed_login_does_not_store_stale_authenticated_user_id(): void
    {
        $staleUser = User::factory()->create();
        $sessionKey = 'login_web_'.sha1(SessionGuard::class);

        $this->withSession([$sessionKey => $staleUser->id]);
        $staleUser->delete();

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'login_failed',
            'user_id' => null,
        ]);
    }
}
