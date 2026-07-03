<x-guest-layout>
    <x-auth-page
        title="Bem-vindo(a) de volta!"
        description="Aceda à sua conta para continuar."
        logo-width="w-64"
    >
        <x-auth-session-status
            class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
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

            <x-auth-text-input
                id="password"
                name="password"
                label="Palavra-passe"
                type="password"
                icon="security"
                placeholder="Introduza a palavra-passe"
                autocomplete="current-password"
                required
            />

            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="rounded border-slate-300 text-purple-700 shadow-sm focus:ring-purple-500"
                >
                <span class="ms-2 text-sm text-slate-600">
                    Lembrar-me
                </span>
            </label>

            <x-auth-button>
                Iniciar sessão
            </x-auth-button>

            @if (Route::has('password.request'))
                <div class="text-center">
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm font-semibold text-purple-700 underline-offset-4 hover:text-purple-900 hover:underline focus:outline-none focus:ring-4 focus:ring-purple-100"
                    >
                        Esqueceu-se da palavra-passe?
                    </a>
                </div>
            @endif
        </form>
    </x-auth-page>

    <p class="mx-auto mt-8 max-w-md text-center text-xs leading-6 text-slate-500">
        Ao iniciar sessão está a aceder a uma área reservada da plataforma MVHAB.
        Utilize apenas credenciais autorizadas.
    </p>
</x-guest-layout>
