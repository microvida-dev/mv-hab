<x-public-layout :title="$program->name">
    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('public.programs.index') }}" class="text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">Programas</a>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <p class="text-sm font-semibold text-ink-500">{{ $program->municipality->name }}</p>
                <span class="rounded-md bg-mvhab-surface px-2.5 py-1 text-xs font-semibold text-mvhab-primary">Publicado</span>
            </div>
            <h1 class="mt-2 max-w-4xl text-3xl font-semibold text-ink-900">{{ $program->name }}</h1>
            <p class="mt-4 max-w-3xl text-lg leading-8 text-ink-600">{{ $program->summary }}</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:px-8">
        <div class="space-y-8">
            <section>
                <h2 class="text-xl font-semibold text-ink-900">Sobre o programa</h2>
                <div class="mt-4 whitespace-pre-line text-base leading-7 text-ink-600">{{ $program->description }}</div>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-ink-900">Regras gerais publicadas</h2>
                <div class="mt-4 divide-y divide-ink-100 border-y border-ink-100">
                    @forelse ($program->rules as $rule)
                        <div class="py-5">
                            <h3 class="font-semibold text-ink-900">{{ $rule->title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $rule->description }}</p>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-ink-500">Sem regras públicas adicionais.</p>
                    @endforelse
                </div>
            </section>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-xl font-semibold text-ink-900">Concursos deste programa</h2>
                    <a href="{{ route('public.contests.index') }}" class="text-sm font-semibold text-mvhab-primary">Todos os concursos</a>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @forelse ($program->contests as $contest)
                        <x-public-contest-card :contest="$contest" />
                    @empty
                        <div class="mv-surface col-span-full p-6 text-sm text-ink-500">Não existem concursos publicados para este programa.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="mv-surface h-fit p-5">
            <h2 class="font-semibold text-ink-900">Período do programa</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-ink-500">Início</dt>
                    <dd class="mt-1 font-semibold text-ink-900">{{ $program->starts_at?->format('d/m/Y') ?? 'Sem data definida' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Fim</dt>
                    <dd class="mt-1 font-semibold text-ink-900">{{ $program->ends_at?->format('d/m/Y') ?? 'Sem data definida' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Publicado em</dt>
                    <dd class="mt-1 font-semibold text-ink-900">{{ $program->published_at?->format('d/m/Y H:i') ?? 'Sem data definida' }}</dd>
                </div>
            </dl>

            @if ($program->legal_basis)
                <h2 class="mt-6 font-semibold text-ink-900">Enquadramento legal</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $program->legal_basis }}</p>
            @endif

            <div class="mt-6 border-t border-ink-100 pt-5">
                @auth
                    <a href="{{ route('dashboard') }}" class="mv-button-primary w-full">
                        @if (Auth::user()->hasRole('candidate'))
                            Área do candidato
                        @elseif (Auth::user()->hasRole('tenant'))
                            Área do inquilino
                        @else
                            Área reservada
                        @endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mv-button-secondary mt-2 w-full">Terminar sessão</button>
                    </form>
                @else
                    <a href="{{ route('register') }}" class="mv-button-primary w-full">Criar conta</a>
                    <a href="{{ route('login') }}" class="mv-button-secondary mt-2 w-full">Entrar</a>
                @endauth
            </div>
        </aside>
    </div>
</x-public-layout>
