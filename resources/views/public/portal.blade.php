<x-public-layout title="Portal público">
    <x-public.hero :stats="$portalStats" />

    <x-public.housing-search />

    <x-public.application-journey />

    <x-public.featured-contests :contests="$contests" />

    <x-public.featured-programs :programs="$programs" />

    <x-public.application-journey />

    <section class="border-y border-ink-100 bg-ink-50">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-8">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Antes de se candidatar</p>
                <h2 class="mt-1 text-2xl font-semibold text-ink-900">Organize a informação necessária</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-ink-600">Confirme se tem consigo os dados de identificação, informação do agregado familiar, comprovativos de rendimentos e documentos habitacionais que possam vir a ser solicitados no aviso de concurso.</p>
                <p class="mt-3 text-sm leading-6 text-ink-500">A lista definitiva de documentos dependerá sempre das regras e do aviso de cada concurso.</p>
            </div>

            <div class="border-l-4 border-signal-400 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Precisa de ajuda?</h2>
                <p class="mt-2 text-sm leading-6 text-ink-500">Consulte as respostas institucionais sobre programas, concursos e preparação da futura candidatura.</p>
                <a href="{{ route('public.faq') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">
                    Ver perguntas frequentes
                    <x-ui-icon name="arrow" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col items-start justify-between gap-6 border-y border-ink-100 py-8 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold text-ink-900">Aceda à área reservada</h2>
                <p class="mt-2 text-sm leading-6 text-ink-500">Entre na sua conta ou crie um acesso para preparar as próximas etapas.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @auth
                    @if (Auth::user()->hasRole('candidate'))
                        <a href="{{ route('candidate.dashboard') }}" class="mv-button-primary">
                            Abrir área do candidato
                        </a>
                    @elseif (Auth::user()->hasRole('tenant'))
                        <a href="{{ route('tenant.dashboard') }}" class="mv-button-primary">
                            Abrir área do inquilino
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="mv-button-primary">
                            Abrir área reservada
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mv-button-secondary">Terminar sessão</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="mv-button-secondary">Entrar</a>
                    <a href="{{ route('register') }}" class="mv-button-primary">Criar conta</a>
                @endauth
            </div>
        </div>
    </section>
</x-public-layout>
