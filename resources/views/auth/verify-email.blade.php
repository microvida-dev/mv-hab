<x-guest-layout>
    <x-auth-page
        title="Verificar endereço de email"
        description="Enviámos uma mensagem para o endereço de email utilizado no registo. Clique na ligação recebida para ativar a sua conta."
    >
        @if (session('status') === 'verification-link-sent')
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                Foi enviada uma nova mensagem de verificação.
            </div>
        @endif

        <div class="mt-8 flex flex-col gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <x-auth-button :icon="false">
                    Reenviar email de verificação
                </x-auth-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="w-full rounded-2xl border border-slate-200 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-purple-100"
                >
                    Terminar sessão
                </button>
            </form>
        </div>
    </x-auth-page>
</x-guest-layout>
