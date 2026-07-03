<x-guest-layout>
    <x-auth-page
        title="Confirmar identidade"
        description="Por motivos de segurança, confirme novamente a sua palavra-passe."
    >
        <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
            @csrf

            <x-auth-text-input
                id="password"
                name="password"
                label="Palavra-passe"
                type="password"
                icon="security"
                placeholder="Introduza a palavra-passe"
                autocomplete="current-password"
                required
                autofocus
            />

            <x-auth-button :icon="false">
                Confirmar
            </x-auth-button>
        </form>
    </x-auth-page>
</x-guest-layout>
