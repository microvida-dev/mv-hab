<x-public-layout title="Habitações" description="Lista pública de habitações municipais disponíveis para consulta.">
    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container">
            <nav aria-label="Breadcrumb" class="text-sm font-semibold text-mvhab-primary">
                <a href="{{ route('public.portal') }}" class="hover:text-mvhab-primary">Início</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <a href="{{ route('public.housing-offer.index') }}" class="hover:text-mvhab-primary">Oferta habitacional</a>
                <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                <span>Habitações</span>
            </nav>

            <div class="mt-6 max-w-3xl">
                <p class="mv-caption">Oferta habitacional</p>

                <h1 class="mv-heading mt-3">
                    Habitações publicadas
                </h1>

                <p class="mv-description mt-6">
                    Consulte as fichas públicas de habitações associadas à oferta municipal.
                </p>
            </div>
        </div>
    </section>

    <section class="mv-section bg-mvhab-surface">
        <div class="mv-container">
            <form method="GET" action="{{ route('public.housing-units.index') }}" class="mv-card mb-10 p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <label>
                        <span class="mv-data-label">Pesquisar</span>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="mv-input mt-1" placeholder="Referência, localidade ou descrição">
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
                        <span class="mv-data-label">Zona</span>
                        <input type="search" name="zone" value="{{ $filters['zone'] ?? '' }}" class="mv-input mt-1" placeholder="Zona pública aproximada">
                    </label>

                    <label>
                        <span class="mv-data-label">Eficiência energética</span>
                        <select name="energy_rating" class="mv-select mt-1">
                            <option value="">Todas</option>
                            @foreach ($filterOptions['energy_ratings'] as $rating)
                                <option value="{{ $rating }}" @selected(($filters['energy_rating'] ?? '') === $rating)>{{ $rating }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="mv-data-label">Renda mínima</span>
                        <input type="number" min="0" step="1" name="rent_min" value="{{ $filters['rent_min'] ?? '' }}" class="mv-input mt-1" placeholder="{{ $filterOptions['rent_min'] ? floor((float) $filterOptions['rent_min']) : '0' }}">
                    </label>

                    <label>
                        <span class="mv-data-label">Renda máxima</span>
                        <input type="number" min="0" step="1" name="rent_max" value="{{ $filters['rent_max'] ?? '' }}" class="mv-input mt-1" placeholder="{{ $filterOptions['rent_max'] ? ceil((float) $filterOptions['rent_max']) : '0' }}">
                    </label>

                    <label>
                        <span class="mv-data-label">Ordenação</span>
                        <select name="sort" class="mv-select mt-1">
                            <option value="published_desc" @selected(($filters['sort'] ?? 'published_desc') === 'published_desc')>Mais recentes</option>
                            <option value="rent_asc" @selected(($filters['sort'] ?? '') === 'rent_asc')>Renda crescente</option>
                            <option value="rent_desc" @selected(($filters['sort'] ?? '') === 'rent_desc')>Renda decrescente</option>
                            <option value="typology" @selected(($filters['sort'] ?? '') === 'typology')>Tipologia</option>
                        </select>
                    </label>

                    <label class="flex items-end gap-2 rounded-2xl border border-ink-100 bg-mvhab-surface px-4 py-3 text-sm font-semibold text-ink-700">
                        <input type="checkbox" name="accessible" value="1" @checked((bool) ($filters['accessible'] ?? false)) class="mv-checkbox">
                        Acessível
                    </label>

                    <label class="flex items-end gap-2 rounded-2xl border border-ink-100 bg-mvhab-surface px-4 py-3 text-sm font-semibold text-ink-700">
                        <input type="checkbox" name="visit_available" value="1" @checked((bool) ($filters['visit_available'] ?? false)) class="mv-checkbox">
                        Visitas disponíveis
                    </label>

                    <div class="flex items-end gap-3">
                        <button class="mv-button-primary w-full justify-center">
                            Filtrar
                        </button>

                        <a href="{{ route('public.housing-units.index') }}" class="mv-button-secondary whitespace-nowrap">
                            Limpar
                        </a>
                    </div>
                </div>
            </form>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($housingUnits as $housingUnit)
                    <x-public-housing-unit-card :housing-unit="$housingUnit" />
                @empty
                    <div class="mv-card p-10 text-center md:col-span-2 xl:col-span-3">
                        <p class="font-semibold text-ink-900">
                            Não existem habitações públicas com estes filtros.
                        </p>

                        <p class="mv-section-description mt-2">
                            Ajuste os critérios de pesquisa ou consulte novamente mais tarde.
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-10">
                {{ $housingUnits->links() }}
            </div>
        </div>
    </section>
</x-public-layout>
