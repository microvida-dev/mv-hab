<x-public-layout
    :title="$seo['title'] ?? $contest->title"
    :description="$seo['description'] ?? $contest->summary"
    :canonical="$seo['canonical'] ?? null"
    :og-type="$seo['og_type'] ?? 'article'"
    :json-ld="$jsonLd ?? null"
>
    @php
        $isOpen = $contest->isOpenForApplications();

        $phaseLabel = $isOpen
            ? 'Candidaturas abertas'
            : ($contest->publicPhase() === 'upcoming' ? 'Abertura futura' : 'Prazo encerrado');

        $phaseBadge = $isOpen
            ? 'mv-badge-success'
            : ($contest->publicPhase() === 'upcoming' ? 'mv-badge-info' : 'mv-badge-neutral');
    @endphp

    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container">
            <nav aria-label="Breadcrumb" class="text-sm font-semibold text-mvhab-primary">
                <a href="{{ route('public.portal') }}" class="hover:text-mvhab-primary">Início</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <a href="{{ route('public.contests.index') }}" class="hover:text-mvhab-primary">Concursos</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <span>{{ $contest->title }}</span>
            </nav>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                <span class="mv-badge mv-badge-neutral">{{ $contest->code }}</span>
                <span class="mv-badge {{ $phaseBadge }}">{{ $phaseLabel }}</span>
            </div>

            <h1 class="mv-heading mt-5 max-w-4xl">
                {{ $contest->title }}
            </h1>

            <p class="mv-description mt-6 max-w-3xl text-lg">
                {{ $contest->summary }}
            </p>
        </div>
    </section>

    <div class="mv-container grid gap-8 py-12 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-12">
            <section class="mv-card p-6">
                <h2 class="mv-card-title">Informação do concurso</h2>

                <div class="mv-description mt-4 whitespace-pre-line">
                    {{ $contest->description }}
                </div>
            </section>

            <section>
                <h2 class="mv-section-title text-xl">Prazos publicados</h2>

                <div class="mv-card mt-5 divide-y divide-ink-100">
                    @foreach ($contest->deadlines as $deadline)
                        <div class="grid gap-4 p-5 sm:grid-cols-[12rem_minmax(0,1fr)]">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $deadline->label }}</p>
                                <p class="mv-section-description mt-1">{{ $deadline->type->label() }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-ink-900">
                                    @if ($deadline->starts_at)
                                        {{ $deadline->starts_at->format('d/m/Y H:i') }} —
                                    @endif
                                    {{ $deadline->ends_at->format('d/m/Y H:i') }}
                                </p>

                                @if ($deadline->description)
                                    <p class="mv-section-description mt-2">
                                        {{ $deadline->description }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($contest->application_instructions)
                <section class="mv-card p-6">
                    <h2 class="mv-card-title">Como preparar a candidatura</h2>

                    <div class="mv-description mt-4 whitespace-pre-line">
                        {{ $contest->application_instructions }}
                    </div>
                </section>
            @endif

            @if (isset($housingUnits) && $housingUnits->isNotEmpty())
                <section>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="mv-caption">Oferta associada</p>
                            <h2 class="mv-section-title mt-2 text-xl">Habitações deste concurso</h2>
                            <p class="mv-section-description">
                                Fichas públicas associadas ao aviso publicado.
                            </p>
                        </div>

                        <a href="{{ route('public.housing-units.index', ['contest' => $contest->slug]) }}" class="mv-link">
                            Ver lista
                        </a>
                    </div>

                    <div class="mt-6 grid gap-6 md:grid-cols-2">
                        @foreach ($housingUnits as $housingUnit)
                            <x-public-housing-unit-card :housing-unit="$housingUnit" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="mv-card p-6">
                <h2 class="mv-card-title">Período de candidatura</h2>

                <dl class="mt-5 grid gap-4 text-sm">
                    <div>
                        <dt class="mv-data-label">Abertura</dt>
                        <dd class="mv-data-value">{{ $contest->opens_at->format('d/m/Y H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="mv-data-label">Encerramento</dt>
                        <dd class="mv-data-value">{{ $contest->closes_at->format('d/m/Y H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="mv-data-label">Publicado em</dt>
                        <dd class="mv-data-value">{{ $contest->published_at?->format('d/m/Y H:i') ?? 'Sem data definida' }}</dd>
                    </div>
                </dl>

                @if ($isOpen)
                    @auth
                        @if (Auth::user()->hasRole('candidate'))
                            <a href="{{ route('candidate.applications.create', $contest) }}" class="mv-button-primary mt-6 w-full">
                                Iniciar candidatura
                            </a>
                        @else
                            <div class="mt-6 rounded-2xl border border-signal-200 bg-signal-50 p-4 text-sm leading-6 text-signal-900">
                                Está autenticado com um perfil interno. Para testar uma candidatura, termine esta sessão e entre ou crie uma conta de candidato.
                            </div>

                            @if (Auth::user()->hasRole('candidate'))
                                <a href="{{ route('candidate.dashboard') }}" class="mv-button-secondary mt-3 w-full">
                                    Área do candidato
                                </a>
                            @elseif (Auth::user()->hasRole('tenant'))
                                <a href="{{ route('tenant.dashboard') }}" class="mv-button-secondary mt-3 w-full">
                                    Área do inquilino
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}" class="mv-button-secondary mt-3 w-full">
                                    Área reservada
                                </a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="mv-button-primary mt-2 w-full">
                                    Terminar sessão
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="mv-button-primary mt-6 w-full">
                            Criar conta para candidatar-me
                        </a>

                        <a href="{{ route('login') }}" class="mv-button-secondary mt-2 w-full">
                            Já tenho conta
                        </a>
                    @endauth

                    <p class="mv-section-description mt-4">
                        A candidatura exige Registo de Adesão finalizado, agregado, rendimentos, situação habitacional e documentação obrigatória.
                    </p>
                @else
                    <p class="mt-6 rounded-2xl bg-ink-50 px-4 py-3 text-sm text-ink-600">
                        A candidatura não está disponível neste momento.
                    </p>
                @endif
            </section>

            <section class="mv-card p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="program" size="lg" />
                    </div>

                    <div>
                        <p class="mv-data-label">Programa</p>
                        <a href="{{ route('public.programs.show', $contest->program->slug) }}" class="mt-1 block font-semibold text-mvhab-primary hover:text-mvhab-primary">
                            {{ $contest->program->name }}
                        </a>
                        <p class="mv-section-description mt-2">
                            {{ $contest->program->municipality->name }}
                        </p>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</x-public-layout>
