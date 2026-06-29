<x-public-layout title="Portal público">
    <x-public.hero
        :contests="$contests"
        :programs="$programs"
    />

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Concursos</p>
                <h2 class="mt-1 text-2xl font-semibold text-ink-900">Oportunidades publicadas</h2>
            </div>
            <a href="{{ route('public.contests.index') }}" class="hidden text-sm font-semibold text-civic-700 hover:text-civic-900 sm:block">Ver todos</a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($contests as $contest)
                <x-public-contest-card :contest="$contest" />
            @empty
                <div class="mv-surface col-span-full p-8 text-center">
                    <p class="font-semibold text-ink-900">Não existem concursos publicados neste momento.</p>
                    <p class="mt-2 text-sm text-ink-500">Consulte novamente esta página para acompanhar novas oportunidades.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="border-y border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div>
                <p class="text-sm font-semibold text-civic-700">Programas</p>
                <h2 class="mt-1 text-2xl font-semibold text-ink-900">Enquadramento municipal</h2>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                @forelse ($programs as $program)
                    <article class="mv-surface p-5">
                        <p class="text-xs font-semibold text-ink-500">{{ $program->municipality->name }}</p>
                        <h3 class="mt-2 text-lg font-semibold text-ink-900">{{ $program->name }}</h3>
                        <p class="mt-2 text-sm leading-6 text-ink-500">{{ $program->summary }}</p>
                        <p class="mt-4 text-sm font-semibold text-ink-700">{{ $program->contests_count }} concurso(s) publicado(s)</p>
                        <a href="{{ route('public.programs.show', $program->slug) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-civic-700 hover:text-civic-900">
                            Consultar programa
                            <x-ui-icon name="arrow" class="h-4 w-4" />
                        </a>
                    </article>
                @empty
                    <div class="mv-surface col-span-full p-8 text-center text-sm text-ink-500">Não existem programas publicados.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold text-civic-700">Como funciona</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink-900">Prepare o seu percurso</h2>
        </div>

        <ol class="mt-7 grid gap-px overflow-hidden rounded-md border border-ink-100 bg-ink-100 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Consulte a oferta', 'Veja habitações publicadas, localização pública, tipologia e renda indicativa.'],
                ['Consulte os concursos', 'Veja avisos, estados e prazos oficiais das oportunidades disponíveis.'],
                ['Prepare os seus dados', 'Reúna informação de identificação, agregado, rendimentos e situação habitacional.'],
                ['Acompanhe o processo', 'A área reservada suportará o acompanhamento nas próximas etapas da plataforma.'],
            ] as $index => [$heading, $copy])
                <li class="bg-white p-5">
                    <span class="text-sm font-semibold text-civic-700">{{ $index + 1 }}</span>
                    <h3 class="mt-3 font-semibold text-ink-900">{{ $heading }}</h3>
                    <p class="mt-2 text-sm leading-6 text-ink-500">{{ $copy }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="border-y border-ink-100 bg-ink-50">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-8">
            <div>
                <p class="text-sm font-semibold text-civic-700">Antes de se candidatar</p>
                <h2 class="mt-1 text-2xl font-semibold text-ink-900">Organize a informação necessária</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-ink-600">Confirme se tem consigo os dados de identificação, informação do agregado familiar, comprovativos de rendimentos e documentos habitacionais que possam vir a ser solicitados no aviso de concurso.</p>
                <p class="mt-3 text-sm leading-6 text-ink-500">A lista definitiva de documentos dependerá sempre das regras e do aviso de cada concurso.</p>
            </div>

            <div class="border-l-4 border-signal-400 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Precisa de ajuda?</h2>
                <p class="mt-2 text-sm leading-6 text-ink-500">Consulte as respostas institucionais sobre programas, concursos e preparação da futura candidatura.</p>
                <a href="{{ route('public.faq') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-civic-700 hover:text-civic-900">
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
