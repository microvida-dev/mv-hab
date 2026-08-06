<x-guest-layout>
    <x-auth-page
        title="Definir nova palavra-passe"
        description="Introduza uma nova palavra-passe para concluir a recuperação da sua conta."
    >
        <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-auth-text-input id="email" name="email" label="Email" type="email" icon="user" :value="old('email', $request->email)" placeholder="nome@exemplo.pt" autocomplete="username" readonly required />

            <x-auth-text-input id="password" name="password" label="Nova palavra-passe" type="password" icon="security" placeholder="Introduza a nova palavra-passe" autocomplete="new-password" aria-describedby="reset-password-requirements" required />

            <x-password-requirements id="reset-password-requirements" />

            <x-auth-text-input id="password_confirmation" name="password_confirmation" label="Confirmar palavra-passe" type="password" icon="security" placeholder="Repita a nova palavra-passe" autocomplete="new-password" required />

            <x-auth-button>
                Atualizar palavra-passe
            </x-auth-button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-purple-700 underline-offset-4 hover:text-purple-900 hover:underline focus:outline-none focus:ring-4 focus:ring-purple-100">
                    Voltar ao início de sessão
                </a>
            </div>
        </form>
    </x-auth-page>
</x-guest-layout>
