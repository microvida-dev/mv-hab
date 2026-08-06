<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\TestPasswords;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered_with_password_requirements(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeText('A palavra-passe deve cumprir os seguintes requisitos:')
            ->assertSeeText('Ter entre 12 e 128 caracteres.')
            ->assertSee('aria-describedby="register-password-requirements"', escape: false);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $this->seed(SystemAccessSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => TestPasswords::VALID,
            'password_confirmation' => TestPasswords::VALID,
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

    public function test_registration_rejects_a_password_that_does_not_meet_the_policy(): void
    {
        Notification::fake();

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'weak@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.test']);
        Notification::assertNothingSent();
    }
}
