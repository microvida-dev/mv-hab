<x-guest-layout>
    <x-auth-page
        title="Recuperar palavra-passe"
        description="Introduza o endereço de email associado à sua conta. Enviaremos um link para definir uma nova palavra-passe."
    >
        <x-auth-session-status
            class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
            @csrf

            <x-auth-text-input
                id="email"
                name="email"
                label="Email"
                type="email"
                icon="user"
                :value="old('email')"
                placeholder="nome@exemplo.pt"
                autocomplete="username"
                required
                autofocus
            />

            <x-auth-button>
                Enviar ligação de recuperação
            </x-auth-button>

            <div class="text-center">
                <a
                    href="{{ route('login') }}"
                    class="text-sm font-semibold text-purple-700 underline-offset-4 hover:text-purple-900 hover:underline focus:outline-none focus:ring-4 focus:ring-purple-100"
                >
                    Voltar ao início de sessão
                </a>
            </div>
        </form>
    </x-auth-page>
</x-guest-layout>
