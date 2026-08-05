<x-mail::message>
# Confirme o seu endereço de email

@if (filled($recipientName))
Olá, {{ $recipientName }}.
@else
Olá.
@endif

Obrigado por criar a sua conta na plataforma **MV-HAB**.

Para proteger os seus dados e confirmar que este endereço de email lhe pertence, valide-o antes de iniciar o Registo de Adesão e utilizar a área reservada.

<x-mail::button :url="$verificationUrl">
Confirmar endereço de email
</x-mail::button>

<x-mail::panel>
Esta ligação é válida durante {{ $expiresInMinutes }} minutos. Por motivos de segurança, não partilhe este email nem a ligação de confirmação.
</x-mail::panel>

Se o botão não funcionar, copie e cole a seguinte ligação no seu navegador:

{{ $verificationUrl }}

Se não criou esta conta, ignore esta mensagem. Nenhuma informação adicional será necessária.

Atentamente,<br>
**Equipa MV-HAB**
</x-mail::message>
