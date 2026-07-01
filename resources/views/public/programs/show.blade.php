<x-public-layout :title="$program->name">
    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container">
            <nav aria-label="Breadcrumb" class="text-sm font-semibold text-mvhab-primary">
                <a href="{{ route('public.portal') }}" class="hover:text-mvhab-primary">Início</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <a href="{{ route('public.programs.index') }}" class="hover:text-mvhab-primary">Programas</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <span>{{ $program->name }}</span>
            </nav>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                <span class="mv-badge mv-badge-neutral">{{ $program->municipality->name }}</span>
                <span class="mv-badge mv-badge-civic">Publicado</span>
            </div>

            <h1 class="mv-heading mt-5 max-w-4xl">
                {{ $program->name }}
            </h1>

            <p class="mv-description mt-6 max-w-3xl text-lg">
                {{ $program->summary }}
            </p>
        </div>
    </section>

    <div class="mv-container grid gap-8 py-12 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-12">
            <section class="mv-card p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="program" size="lg" />
                    </div>

                    <div>
                        <h2 class="mv-card-title">Sobre o programa</h2>
                        <div class="mv-description mt-4 whitespace-pre-line">
                            {{ $program->description }}
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="mv-section-title text-xl">Regras gerais publicadas</h2>

                <div class="mv-card mt-5 divide-y divide-ink-100">
                    @forelse ($program->rules as $rule)
                        <article class="p-5">
                            <h3 class="font-semibold text-ink-900">{{ $rule->title }}</h3>
                            <p class="mv-section-description mt-2">{{ $rule->description }}</p>
                        </article>
                    @empty
                        <div class="p-6 text-sm text-ink-500">
                            Sem regras públicas adicionais.
                        </div>
                    @endforelse
                </div>
            </section>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="mv-caption">Concursos</p>
                        <h2 class="mv-section-title mt-2 text-xl">
                            Concursos deste programa
                        </h2>
                    </div>

                    <a href="{{ route('public.contests.index') }}" class="mv-link">
                        Todos os concursos
                    </a>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @forelse ($program->contests as $contest)
                        <x-public-contest-card :contest="$contest" />
                    @empty
                        <div class="mv-card col-span-full p-8 text-center">
                            <p class="font-semibold text-ink-900">
                                Não existem concursos publicados para este programa.
                            </p>
                            <p class="mv-section-description mt-2">
                                Quando forem publicados concursos associados, ficarão disponíveis nesta página.
                            </p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="mv-card p-6">
                <h2 class="mv-card-title">Período do programa</h2>

                <dl class="mt-5 grid gap-4 text-sm">
                    <div>
                        <dt class="mv-data-label">Início</dt>
                        <dd class="mv-data-value">{{ $program->starts_at?->format('d/m/Y') ?? 'Sem data definida' }}</dd>
                    </div>

                    <div>
                        <dt class="mv-data-label">Fim</dt>
                        <dd class="mv-data-value">{{ $program->ends_at?->format('d/m/Y') ?? 'Sem data definida' }}</dd>
                    </div>

                    <div>
                        <dt class="mv-data-label">Publicado em</dt>
                        <dd class="mv-data-value">{{ $program->published_at?->format('d/m/Y H:i') ?? 'Sem data definida' }}</dd>
                    </div>
                </dl>
            </section>

            @if ($program->legal_basis)
                <section class="mv-card p-6">
                    <h2 class="mv-card-title">Enquadramento legal</h2>
                    <p class="mv-section-description mt-3 whitespace-pre-line">
                        {{ $program->legal_basis }}
                    </p>
                </section>
            @endif

            <section class="mv-card p-6">
                <h2 class="mv-card-title">Acesso à plataforma</h2>
                <p class="mv-section-description mt-2">
                    Entre na área reservada para preparar ou acompanhar processos associados aos programas municipais.
                </p>

                <div class="mt-5 grid gap-3">
                    @auth
                        @if (Auth::user()->hasRole('candidate'))
                            <a href="{{ route('candidate.dashboard') }}" class="mv-button-primary justify-center">
                                Área do candidato
                            </a>
                        @elseif (Auth::user()->hasRole('tenant'))
                            <a href="{{ route('tenant.dashboard') }}" class="mv-button-primary justify-center">
                                Área do inquilino
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="mv-button-primary justify-center">
                                Área reservada
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="mv-button-secondary w-full justify-center">
                                Terminar sessão
                            </button>
                        </form>
                    @else
                        <a href="{{ route('register') }}" class="mv-button-primary justify-center">
                            Criar conta
                        </a>

                        <a href="{{ route('login') }}" class="mv-button-secondary justify-center">
                            Entrar
                        </a>
                    @endauth
                </div>
            </section>
        </aside>
    </div>
</x-public-layout>
