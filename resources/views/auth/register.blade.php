<x-guest-layout>
    <x-auth-page
        title="Criar conta"
        description="Registe-se para aceder à plataforma MVHAB."
    >
        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf

            <x-auth-text-input id="name" name="name" label="Nome" icon="user" :value="old('name')" placeholder="Nome completo" autocomplete="name" required autofocus />

            <x-auth-text-input id="email" name="email" label="Email" type="email" icon="user" :value="old('email')" placeholder="nome@exemplo.pt" autocomplete="username" required />

            <x-auth-text-input id="password" name="password" label="Palavra-passe" type="password" icon="security" placeholder="Defina uma palavra-passe" autocomplete="new-password" aria-describedby="register-password-requirements" required />

            <x-password-requirements id="register-password-requirements" />

            <x-auth-text-input id="password_confirmation" name="password_confirmation" label="Confirmar palavra-passe" type="password" icon="security" placeholder="Repita a palavra-passe" autocomplete="new-password" required />

            <x-auth-button>
                Criar conta
            </x-auth-button>

            <div class="text-center">
                <a class="text-sm font-semibold text-purple-700 underline-offset-4 hover:text-purple-900 hover:underline focus:outline-none focus:ring-4 focus:ring-purple-100" href="{{ route('login') }}">
                    Já tem conta? Iniciar sessão
                </a>
            </div>
        </form>
    </x-auth-page>
</x-guest-layout>
