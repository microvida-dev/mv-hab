<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $this->seed(SystemAccessSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()
            ->where('email', 'test@example.test')
            ->firstOrFail();

        $this->assertAuthenticated();
        $this->assertTrue($user->hasRole('candidate'));
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, VerifyEmail::class);

        $response->assertRedirect(route('verification.notice', absolute: false));
    }
}
