<x-public-layout
    :title="$seo['title'] ?? $contest->title"
    :description="$seo['description'] ?? $contest->summary"
    :canonical="$seo['canonical'] ?? null"
    :json-ld="$jsonLd ?? null"
>
    @php
        $isOpen = $contest->isOpenForApplications();
    @endphp

    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('public.contests.index') }}" class="text-sm font-semibold text-civic-700 hover:text-civic-900">Concursos</a>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="rounded-md bg-white px-2.5 py-1 text-xs font-semibold text-ink-700 ring-1 ring-ink-100">{{ $contest->code }}</span>
                <span class="rounded-md px-2.5 py-1 text-xs font-semibold {{ $isOpen ? 'bg-civic-50 text-civic-900' : 'bg-ink-100 text-ink-700' }}">
                    {{ $isOpen ? 'Candidaturas abertas' : ($contest->publicPhase() === 'upcoming' ? 'Abertura futura' : 'Prazo encerrado') }}
                </span>
            </div>
            <h1 class="mt-4 max-w-4xl text-3xl font-semibold text-ink-900">{{ $contest->title }}</h1>
            <p class="mt-4 max-w-3xl text-lg leading-8 text-ink-600">{{ $contest->summary }}</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-8">
        <div class="space-y-8">
            <section>
                <h2 class="text-xl font-semibold text-ink-900">Informação do concurso</h2>
                <div class="mt-4 whitespace-pre-line text-base leading-7 text-ink-600">{{ $contest->description }}</div>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-ink-900">Prazos publicados</h2>
                <div class="mt-4 divide-y divide-ink-100 border-y border-ink-100">
                    @foreach ($contest->deadlines as $deadline)
                        <div class="grid gap-2 py-5 sm:grid-cols-[12rem_minmax(0,1fr)]">
                            <div>
                                <p class="text-sm font-semibold text-ink-900">{{ $deadline->label }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $deadline->type->label() }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-ink-700">
                                    @if ($deadline->starts_at)
                                        {{ $deadline->starts_at->format('d/m/Y H:i') }} a
                                    @endif
                                    {{ $deadline->ends_at->format('d/m/Y H:i') }}
                                </p>
                                @if ($deadline->description)
                                    <p class="mt-1 text-sm leading-6 text-ink-500">{{ $deadline->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($contest->application_instructions)
                <section>
                    <h2 class="text-xl font-semibold text-ink-900">Como preparar a candidatura</h2>
                    <div class="mt-4 whitespace-pre-line text-base leading-7 text-ink-600">{{ $contest->application_instructions }}</div>
                </section>
            @endif

            @if (isset($housingUnits) && $housingUnits->isNotEmpty())
                <section>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-ink-900">Habitações deste concurso</h2>
                            <p class="mt-1 text-sm text-ink-500">Fichas públicas associadas ao aviso publicado.</p>
                        </div>
                        <a href="{{ route('public.housing-units.index', ['contest' => $contest->slug]) }}" class="text-sm font-semibold text-civic-700 hover:text-civic-900">Ver lista</a>
                    </div>
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        @foreach ($housingUnits as $housingUnit)
                            <x-public-housing-unit-card :housing-unit="$housingUnit" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <section class="mv-surface p-5">
                <h2 class="font-semibold text-ink-900">Período de candidatura</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-ink-500">Abertura</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $contest->opens_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Encerramento</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $contest->closes_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Publicado em</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $contest->published_at?->format('d/m/Y H:i') ?? 'Sem data definida' }}</dd>
                    </div>
                </dl>

                @if ($isOpen)
                    @auth
                        @if (Auth::user()->hasRole('candidate'))
                            <a href="{{ route('candidate.applications.create', $contest) }}" class="mv-button-primary mt-5 w-full">Iniciar candidatura</a>
                        @else
                            <div class="mt-5 rounded-md border border-signal-200 bg-signal-50 p-4 text-sm leading-6 text-signal-900">
                                Está autenticado com um perfil interno. Para testar uma candidatura, termine esta sessão e entre ou crie uma conta de candidato.
                            </div>
                            <a href="{{ route('dashboard') }}" class="mv-button-secondary mt-3 w-full">Abrir backoffice</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="mv-button-primary mt-2 w-full">Terminar sessão</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="mv-button-primary mt-5 w-full">Criar conta para candidatar-me</a>
                        <a href="{{ route('login') }}" class="mv-button-secondary mt-2 w-full">Já tenho conta</a>
                    @endauth
                    <p class="mt-3 text-xs leading-5 text-ink-500">A candidatura exige Registo de Adesão finalizado, agregado, rendimentos, situação habitacional e documentação obrigatória.</p>
                @else
                    <p class="mt-5 rounded-md bg-ink-50 px-3 py-3 text-sm text-ink-600">A candidatura não está disponível neste momento.</p>
            @endif
            </section>

            <section class="mv-surface p-5">
                <p class="text-sm text-ink-500">Programa</p>
                <a href="{{ route('public.programs.show', $contest->program->slug) }}" class="mt-1 block font-semibold text-civic-700 hover:text-civic-900">{{ $contest->program->name }}</a>
                <p class="mt-3 text-sm text-ink-500">{{ $contest->program->municipality->name }}</p>
            </section>
        </aside>
    </div>
</x-public-layout>
