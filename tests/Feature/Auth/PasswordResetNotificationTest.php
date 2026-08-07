<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use LogicException;
use stdClass;
use Tests\TestCase;

class PasswordResetNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_email_is_branded_and_in_portuguese(): void
    {
        $user = User::factory()->create([
            'name' => 'Bruno Teste',
            'email' => 'bruno.teste@example.test',
        ]);

        $mail = (new ResetPasswordNotification('reset-token'))
            ->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame(
            'Redefinir a palavra-passe — MV-HAB',
            $mail->subject,
        );
        $this->assertSame(
            'mail.auth.reset-password',
            $mail->markdown,
        );
        $this->assertSame('mvhab', $mail->theme);

        $html = (string) $mail->render();
        $text = (string) app(Markdown::class)
            ->theme('mvhab')
            ->renderText(
                'mail.auth.reset-password',
                $mail->data(),
            );

        foreach ([$html, $text] as $rendered) {
            $this->assertStringContainsString(
                'Redefinir a palavra-passe',
                $rendered,
            );
            $this->assertStringContainsString(
                'Redefinir palavra-passe',
                $rendered,
            );
            $this->assertStringContainsString(
                'Bruno Teste',
                $rendered,
            );
            $this->assertStringContainsString(
                'Equipa MV-HAB',
                $rendered,
            );
            $this->assertStringNotContainsString(
                'Reset Password',
                $rendered,
            );
            $this->assertStringNotContainsString(
                'You are receiving this email',
                $rendered,
            );
            $this->assertStringNotContainsString(
                'Hello!',
                $rendered,
            );
        }
    }

    public function test_password_reset_email_uses_the_official_route_and_configured_expiry(): void
    {
        config()->set('auth.defaults.passwords', 'users');
        config()->set('auth.passwords.users.expire', 37);

        $user = User::factory()->create([
            'name' => 'Utilizador Teste',
            'email' => 'reset@example.test',
        ]);

        $token = 'test-reset-token';
        $mail = (new ResetPasswordNotification($token))
            ->toMail($user);
        $data = $mail->data();

        $expectedUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);

        $this->assertSame(
            $expectedUrl,
            $data['resetUrl'] ?? null,
        );
        $this->assertSame(
            37,
            $data['expiresInMinutes'] ?? null,
        );
        $this->assertSame(
            $user->name,
            $data['recipientName'] ?? null,
        );

        $html = (string) $mail->render();

        $this->assertStringContainsString(
            'Esta ligação é válida durante 37 minutos.',
            $html,
        );
        $this->assertStringContainsString($token, $html);
        $this->assertStringContainsString(
            'reset%40example.test',
            $html,
        );
    }

    public function test_password_reset_notification_only_accepts_application_users(): void
    {
        $this->expectException(LogicException::class);

        (new ResetPasswordNotification('reset-token'))
            ->toMail(new stdClass);
    }
}
