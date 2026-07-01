<x-public-layout
    :title="$seo['title'] ?? 'Oferta Habitacional'"
    :description="$seo['description'] ?? 'Oferta habitacional municipal publicada.'"
    :canonical="$seo['canonical'] ?? null"
>
    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container">
            <nav aria-label="Breadcrumb" class="text-sm font-semibold text-mvhab-primary">
                <a href="{{ route('public.portal') }}" class="hover:text-mvhab-primary">Início</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <span>Oferta habitacional</span>
            </nav>

            <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-end">
                <div>
                    <p class="mv-caption">Oferta habitacional</p>

                    <h1 class="mv-heading mt-3">
                        {{ $settings['portal_title'] ?? 'Oferta Habitacional' }}
                    </h1>

                    <p class="mv-description mt-6">
                        {{ $settings['portal_description'] ?? 'Consulte concursos e habitações municipais publicadas.' }}
                    </p>
                </div>

                <div class="mv-card p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                            <x-mv-icon name="security" size="lg" />
                        </div>

                        <div>
                            <h2 class="mv-card-title">Consulta pública</h2>
                            <p class="mv-section-description mt-2">
                                As fichas públicas não apresentam dados pessoais de candidatos nem documentos reservados.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-ink-100 bg-mvhab-surface">
        <div class="mv-container py-8">
            <form method="GET" action="{{ route('public.housing-offer.index') }}" class="mv-card grid gap-4 p-6 md:grid-cols-2 lg:grid-cols-6">
                <label class="lg:col-span-2">
                    <span class="mv-data-label">Pesquisa</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="mv-input mt-1" placeholder="Código, freguesia ou descrição">
                </label>

                <label>
                    <span class="mv-data-label">Tipologia</span>
                    <select name="typology" class="mv-select mt-1">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['typologies'] as $typology)
                            <option value="{{ $typology }}" @selected(($filters['typology'] ?? '') === $typology)>{{ $typology }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mv-data-label">Freguesia</span>
                    <select name="parish" class="mv-select mt-1">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['parishes'] as $parish)
                            <option value="{{ $parish }}" @selected(($filters['parish'] ?? '') === $parish)>{{ $parish }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mv-data-label">Localidade</span>
                    <select name="locality" class="mv-select mt-1">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['localities'] as $locality)
                            <option value="{{ $locality }}" @selected(($filters['locality'] ?? '') === $locality)>{{ $locality }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mv-data-label">Estado</span>
                    <select name="public_status" class="mv-select mt-1">
                        <option value="">Todos</option>
                        @foreach ($filterOptions['statuses'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['public_status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mv-data-label">Eficiência</span>
                    <select name="energy_rating" class="mv-select mt-1">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['energy_ratings'] as $rating)
                            <option value="{{ $rating }}" @selected(($filters['energy_rating'] ?? '') === $rating)>{{ $rating }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mv-data-label">Ordenar</span>
                    <select name="sort" class="mv-select mt-1">
                        <option value="published_desc" @selected(($filters['sort'] ?? '') === 'published_desc')>Publicação</option>
                        <option value="rent_asc" @selected(($filters['sort'] ?? '') === 'rent_asc')>Renda crescente</option>
                        <option value="rent_desc" @selected(($filters['sort'] ?? '') === 'rent_desc')>Renda decrescente</option>
                        <option value="typology" @selected(($filters['sort'] ?? '') === 'typology')>Tipologia</option>
                    </select>
                </label>

                <label class="flex items-end gap-2 rounded-2xl border border-ink-100 bg-mvhab-surface px-4 py-3 text-sm font-semibold text-ink-700">
                    <input type="checkbox" name="visit_available" value="1" @checked((bool) ($filters['visit_available'] ?? false)) class="mv-checkbox">
                    Visitas disponíveis
                </label>

                <div class="flex flex-wrap items-end gap-2 lg:col-span-6">
                    <button type="submit" class="mv-button-primary">
                        Filtrar oferta
                    </button>

                    <a href="{{ route('public.housing-offer.index') }}" class="mv-button-secondary">
                        Limpar
                    </a>

                    <a href="{{ route('public.contests.index') }}" class="mv-button-secondary">
                        Ver concursos
                    </a>
                </div>
            </form>
        </div>
    </section>

    <div class="mv-container grid gap-8 py-12 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-12">
            <section>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="mv-caption">Habitações</p>
                        <h2 class="mv-section-title mt-2 text-xl">Habitações publicadas</h2>
                        <p class="mv-section-description">
                            {{ $housingUnits->total() }} resultados disponíveis para consulta.
                        </p>
                    </div>

                    <a href="{{ route('public.housing-units.index', request()->query()) }}" class="mv-link hidden sm:block">
                        Abrir lista completa
                    </a>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @forelse ($housingUnits as $housingUnit)
                        <x-public-housing-unit-card :housing-unit="$housingUnit" />
                    @empty
                        <div class="mv-card p-10 text-center md:col-span-2">
                            <p class="font-semibold text-ink-900">Não existem habitações públicas com estes filtros.</p>
                            <p class="mv-section-description mt-2">Ajuste os critérios de pesquisa ou consulte novamente mais tarde.</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-8">{{ $housingUnits->links() }}</div>
            </section>

            <section>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="mv-caption">Concursos</p>
                        <h2 class="mv-section-title mt-2 text-xl">Concursos com oferta habitacional</h2>
                        <p class="mv-section-description">
                            Consulte prazos, condições públicas e habitações associadas.
                        </p>
                    </div>

                    <a href="{{ route('public.contests.index') }}" class="mv-link">
                        Todos os concursos
                    </a>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @forelse ($contests as $contest)
                        <x-public-contest-card :contest="$contest" />
                    @empty
                        <div class="mv-card p-8 text-sm text-ink-500 md:col-span-2">
                            Não existem concursos abertos neste momento.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="mv-card p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="mv-card-title">Mapa da oferta</h2>
                        <p class="mv-section-description">
                            {{ count($markers) }} localizações públicas.
                        </p>
                    </div>

                    <a href="{{ route('public.housing-map.index', request()->query()) }}" class="mv-link text-sm">
                        JSON
                    </a>
                </div>

                <div class="mt-5 rounded-2xl border border-mvhab-support/30 bg-mvhab-surface p-4">
                    @forelse ($markers as $marker)
                        <a href="{{ $marker['url'] }}" class="mb-3 block rounded-2xl bg-white p-4 text-sm shadow-surface last:mb-0 hover:text-mvhab-primary">
                            <span class="font-semibold text-ink-900">{{ $marker['title'] }}</span>
                            <span class="mt-1 block text-ink-500">{{ $marker['location'] }} · {{ $marker['typology'] }}</span>
                        </a>
                    @empty
                        <p class="text-sm leading-6 text-ink-600">
                            O mapa será apresentado quando existirem habitações publicadas com coordenadas públicas.
                        </p>
                    @endforelse
                </div>
            </section>

            <section class="mv-card p-6">
                <h2 class="mv-card-title">Ligações úteis</h2>

                <div class="mt-4 grid gap-3 text-sm">
                    @forelse ($links as $link)
                        <a href="{{ $link->url }}" @if ($link->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif class="mv-link">
                            {{ $link->label }}

                            @if ($link->description)
                                <span class="mt-1 block font-normal leading-5 text-ink-500">
                                    {{ $link->description }}
                                </span>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm leading-6 text-ink-500">
                            Sem ligações institucionais configuradas.
                        </p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</x-public-layout>
