<x-public-layout
    :title="$seo['title'] ?? 'Oferta Habitacional'"
    :description="$seo['description'] ?? 'Oferta habitacional municipal publicada.'"
    :canonical="$seo['canonical'] ?? null"
>
    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm font-semibold text-civic-700">
                <a href="{{ route('public.portal') }}" class="hover:text-civic-900">Início</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <span>Oferta habitacional</span>
            </nav>
            <div class="mt-3 grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-end">
                <div>
                    <h1 class="max-w-4xl text-3xl font-semibold text-ink-900 sm:text-4xl">{{ $settings['portal_title'] ?? 'Oferta Habitacional' }}</h1>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-ink-600">{{ $settings['portal_description'] ?? 'Consulte concursos e habitações municipais publicadas.' }}</p>
                </div>
                <div class="rounded-md border border-civic-100 bg-white p-5">
                    <p class="text-sm font-semibold text-ink-900">Consulta pública</p>
                    <p class="mt-2 text-sm leading-6 text-ink-500">As fichas públicas não apresentam dados pessoais de candidatos nem documentos reservados.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-ink-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('public.housing-offer.index') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                <label class="lg:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Pesquisa</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="mt-1 w-full rounded-md border-ink-200 text-sm" placeholder="Código, freguesia ou descrição">
                </label>

                <label>
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Tipologia</span>
                    <select name="typology" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['typologies'] as $typology)
                            <option value="{{ $typology }}" @selected(($filters['typology'] ?? '') === $typology)>{{ $typology }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Freguesia</span>
                    <select name="parish" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['parishes'] as $parish)
                            <option value="{{ $parish }}" @selected(($filters['parish'] ?? '') === $parish)>{{ $parish }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Localidade</span>
                    <select name="locality" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['localities'] as $locality)
                            <option value="{{ $locality }}" @selected(($filters['locality'] ?? '') === $locality)>{{ $locality }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Estado</span>
                    <select name="public_status" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todos</option>
                        @foreach ($filterOptions['statuses'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['public_status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Eficiência</span>
                    <select name="energy_rating" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['energy_ratings'] as $rating)
                            <option value="{{ $rating }}" @selected(($filters['energy_rating'] ?? '') === $rating)>{{ $rating }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Ordenar</span>
                    <select name="sort" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="published_desc" @selected(($filters['sort'] ?? '') === 'published_desc')>Publicação</option>
                        <option value="rent_asc" @selected(($filters['sort'] ?? '') === 'rent_asc')>Renda crescente</option>
                        <option value="rent_desc" @selected(($filters['sort'] ?? '') === 'rent_desc')>Renda decrescente</option>
                        <option value="typology" @selected(($filters['sort'] ?? '') === 'typology')>Tipologia</option>
                    </select>
                </label>

                <label class="flex items-end gap-2 rounded-md border border-ink-100 bg-ink-50 px-3 py-2 text-sm font-semibold text-ink-700">
                    <input type="checkbox" name="visit_available" value="1" @checked((bool) ($filters['visit_available'] ?? false)) class="rounded border-ink-300 text-civic-700">
                    Visitas disponíveis
                </label>

                <div class="flex items-end gap-2 lg:col-span-6">
                    <button type="submit" class="mv-button-primary">Filtrar oferta</button>
                    <a href="{{ route('public.housing-offer.index') }}" class="mv-button-secondary">Limpar</a>
                    <a href="{{ route('public.contests.index') }}" class="mv-button-secondary">Ver concursos</a>
                </div>
            </form>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:px-8">
        <div class="space-y-10">
            <section>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-ink-900">Habitações publicadas</h2>
                        <p class="mt-1 text-sm text-ink-500">{{ $housingUnits->total() }} resultados disponíveis para consulta.</p>
                    </div>
                    <a href="{{ route('public.housing-units.index', request()->query()) }}" class="hidden text-sm font-semibold text-civic-700 hover:text-civic-900 sm:block">Abrir lista completa</a>
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    @forelse ($housingUnits as $housingUnit)
                        <x-public-housing-unit-card :housing-unit="$housingUnit" />
                    @empty
                        <div class="rounded-md border border-ink-100 bg-white p-8 text-center text-sm text-ink-500 md:col-span-2">Não existem habitações públicas com estes filtros.</div>
                    @endforelse
                </div>

                <div class="mt-8">{{ $housingUnits->links() }}</div>
            </section>

            <section>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-ink-900">Concursos com oferta habitacional</h2>
                        <p class="mt-1 text-sm text-ink-500">Consulte prazos, condições públicas e habitações associadas.</p>
                    </div>
                    <a href="{{ route('public.contests.index') }}" class="text-sm font-semibold text-civic-700 hover:text-civic-900">Todos os concursos</a>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @forelse ($contests as $contest)
                        <x-public-contest-card :contest="$contest" />
                    @empty
                        <div class="rounded-md border border-ink-100 bg-white p-6 text-sm text-ink-500 md:col-span-2">Não existem concursos abertos neste momento.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-md border border-ink-100 bg-white p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-ink-900">Mapa da oferta</h2>
                        <p class="mt-1 text-sm text-ink-500">{{ count($markers) }} localizações públicas.</p>
                    </div>
                    <a href="{{ route('public.housing-map.index', request()->query()) }}" class="text-sm font-semibold text-civic-700 hover:text-civic-900">JSON</a>
                </div>

                <div class="mt-4 rounded-md border border-civic-100 bg-civic-50 p-4">
                    @forelse ($markers as $marker)
                        <a href="{{ $marker['url'] }}" class="mb-3 block rounded-md bg-white p-3 text-sm shadow-sm last:mb-0">
                            <span class="font-semibold text-ink-900">{{ $marker['title'] }}</span>
                            <span class="mt-1 block text-ink-500">{{ $marker['location'] }} · {{ $marker['typology'] }}</span>
                        </a>
                    @empty
                        <p class="text-sm leading-6 text-ink-600">O mapa será apresentado quando existirem habitações publicadas com coordenadas públicas.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-md border border-ink-100 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Ligações úteis</h2>
                <div class="mt-4 grid gap-3 text-sm">
                    @forelse ($links as $link)
                        <a href="{{ $link->url }}" @if ($link->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif class="font-semibold text-civic-700 hover:text-civic-900">
                            {{ $link->label }}
                            @if ($link->description)
                                <span class="mt-1 block font-normal leading-5 text-ink-500">{{ $link->description }}</span>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm leading-6 text-ink-500">Sem ligações institucionais configuradas.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</x-public-layout>
