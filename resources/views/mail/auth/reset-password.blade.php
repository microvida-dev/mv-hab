<x-mail::message>
# Redefinir a palavra-passe

@if (filled($recipientName))
Olá, {{ $recipientName }}.
@else
Olá.
@endif

Recebemos um pedido para redefinir a palavra-passe da sua conta **MV-HAB**.

Utilize o botão abaixo para escolher uma nova palavra-passe.

<x-mail::button :url="$resetUrl">
Redefinir palavra-passe
</x-mail::button>

<x-mail::panel>
Esta ligação é válida durante {{ $expiresInMinutes }} minutos. Por motivos de segurança, não partilhe este email nem a ligação de recuperação.
</x-mail::panel>

Se o botão não funcionar, copie e cole a seguinte ligação no seu navegador:

{{ $resetUrl }}

Se não solicitou esta alteração, ignore esta mensagem. A sua palavra-passe permanecerá inalterada.

Atentamente,<br>
**Equipa MV-HAB**
</x-mail::message>
