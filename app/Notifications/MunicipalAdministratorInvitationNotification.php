<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LogicException;

final class MunicipalAdministratorInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly int $expiresInMinutes,
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
            throw new LogicException('O convite administrativo municipal apenas pode ser enviado a utilizadores.');
        }

        $email = $notifiable->getEmailForPasswordReset();

        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ]);

        return (new MailMessage)
            ->subject('Configuração do acesso administrativo municipal — MV-HAB')
            ->greeting('Configuração de acesso ao MV-HAB')
            ->line('Foi criada uma conta de administração municipal para utilização na plataforma MV-HAB.')
            ->line('Defina uma palavra-passe através do botão abaixo. O link é temporário e de utilização única.')
            ->action('Definir palavra-passe', $url)
            ->line("O link expira em {$this->expiresInMinutes} minutos.")
            ->line('Após definir a palavra-passe, será necessário configurar e confirmar o MFA antes de utilizar o backoffice.');
    }
}
