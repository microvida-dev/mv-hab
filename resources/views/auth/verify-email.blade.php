<x-guest-layout>
    <x-auth-page
        title="Confirme o seu endereço de email"
        description="Antes de utilizar a área reservada, confirme que controla o endereço indicado no registo."
    >
        @if (session('status') === 'verification-link-sent')
            <div
                role="status"
                class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            >
                Foi enviada uma nova mensagem de verificação.
            </div>
        @endif

        <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-4 text-sm text-slate-700">
            <p class="font-semibold text-slate-900">Mensagem enviada para</p>
            <p class="mt-1 break-all">{{ request()->user()?->email }}</p>
            <p class="mt-3 leading-6">
                O acesso ao Registo de Adesão, agregado, rendimentos, documentos,
                candidaturas e Área do Inquilino ficará disponível depois da confirmação.
            </p>
        </div>

        <div class="mt-8 flex flex-col gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <x-auth-button :icon="false">
                    Reenviar email de verificação
                </x-auth-button>
            </form>

            <a
                href="{{ route('profile.edit') }}"
                class="inline-flex w-full items-center justify-center rounded-2xl border border-blue-200 px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-100"
            >
                Corrigir endereço de email
            </a>

            <a
                href="{{ route('public.portal') }}"
                class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
            >
                Ir para o Portal Público
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="w-full rounded-2xl border border-slate-200 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                >
                    Terminar sessão
                </button>
            </form>
        </div>
    </x-auth-page>
</x-guest-layout>
