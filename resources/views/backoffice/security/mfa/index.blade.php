<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Autenticação multifator</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @isset($setupDevice)
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Configurar aplicação autenticadora</h2>
                    <p class="mt-2 text-sm text-ink-600">Chave TOTP de demonstração: <span class="font-mono font-semibold">{{ $setupDevice->secret_encrypted }}</span></p>
                    <form method="POST" action="{{ route('backoffice.security.mfa.confirm', $setupDevice) }}" class="mt-4 flex gap-3">
                        @csrf
                        <input name="code" class="mv-input max-w-40" placeholder="000000" required>
                        <button class="mv-button-primary">Confirmar</button>
                    </form>
                </section>
            @endisset

            @isset($recoveryCodes)
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Códigos de recuperação</h2>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($recoveryCodes as $code)
                            <code class="rounded-md bg-ink-100 px-3 py-2 text-sm">{{ $code }}</code>
                        @endforeach
                    </div>
                </section>
            @endisset

            <section class="mv-surface p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-ink-900">Estado MFA</h2>
                        <p class="mt-1 text-sm text-ink-500">Obrigatório para perfis de backoffice: {{ $requiresMfa ? 'sim' : 'não' }} · sessão validada: {{ $sessionVerified ? 'sim' : 'não' }}</p>
                    </div>
                    <form method="POST" action="{{ route('backoffice.security.mfa.enable') }}">
                        @csrf
                        <button class="mv-button-primary">Adicionar dispositivo</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('backoffice.security.mfa.verify') }}" class="mt-5 flex gap-3">
                    @csrf
                    <input name="code" class="mv-input max-w-56" placeholder="Código MFA ou recuperação" required>
                    <button class="mv-button-secondary">Validar sessão</button>
                </form>
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="divide-y divide-ink-100">
                    @foreach ($devices as $device)
                        <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $device->name }}</p>
                                <p class="text-sm text-ink-500">Confirmado: {{ $device->confirmed_at?->format('d/m/Y H:i') ?? 'não' }} · Desativado: {{ $device->disabled_at?->format('d/m/Y H:i') ?? 'não' }}</p>
                            </div>
                            @if (! $device->disabled_at)
                                <form method="POST" action="{{ route('backoffice.security.mfa.disable', $device) }}">
                                    @csrf
                                    <button class="mv-button-secondary">Desativar</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('backoffice.security.mfa.recovery-codes.regenerate') }}" class="border-t border-ink-100 p-5">
                    @csrf
                    <button class="mv-button-secondary">Gerar novos códigos de recuperação</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
