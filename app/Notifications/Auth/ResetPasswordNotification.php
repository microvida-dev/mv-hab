<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LogicException;

final class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof User) {
            throw new LogicException(
                'A recuperação de palavra-passe apenas pode ser enviada a utilizadores.',
            );
        }

        $broker = (string) config('auth.defaults.passwords', 'users');
        $expiresInMinutes = (int) config(
            "auth.passwords.{$broker}.expire",
            60,
        );

        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Redefinir a palavra-passe — MV-HAB')
            ->markdown('mail.auth.reset-password', [
                'resetUrl' => $resetUrl,
                'recipientName' => $notifiable->name,
                'expiresInMinutes' => $expiresInMinutes,
            ])
            ->theme('mvhab');
    }
}
