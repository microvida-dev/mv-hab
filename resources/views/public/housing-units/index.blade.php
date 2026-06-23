<x-public-layout title="Habitações" description="Lista pública de habitações municipais disponíveis para consulta.">
    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('public.housing-offer.index') }}" class="text-sm font-semibold text-civic-700 hover:text-civic-900">Oferta habitacional</a>
            <h1 class="mt-3 text-3xl font-semibold text-ink-900">Habitações publicadas</h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-ink-600">Consulte as fichas públicas de habitações associadas à oferta municipal.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('public.housing-units.index') }}" class="mb-8 rounded-md border border-ink-100 bg-white p-5">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Pesquisar</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="mt-1 w-full rounded-md border-ink-200 text-sm" placeholder="Referência, localidade ou descrição">
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Tipologia</span>
                    <select name="typology" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['typologies'] as $typology)
                            <option value="{{ $typology }}" @selected(($filters['typology'] ?? '') === $typology)>{{ $typology }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Freguesia</span>
                    <select name="parish" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todas</option>
                        @foreach ($filterOptions['parishes'] as $parish)
                            <option value="{{ $parish }}" @selected(($filters['parish'] ?? '') === $parish)>{{ $parish }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Estado</span>
                    <select name="public_status" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="">Todos</option>
                        @foreach ($filterOptions['statuses'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['public_status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Renda mínima</span>
                    <input type="number" min="0" step="1" name="rent_min" value="{{ $filters['rent_min'] ?? '' }}" class="mt-1 w-full rounded-md border-ink-200 text-sm" placeholder="{{ $filterOptions['rent_min'] ? floor((float) $filterOptions['rent_min']) : '0' }}">
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Renda máxima</span>
                    <input type="number" min="0" step="1" name="rent_max" value="{{ $filters['rent_max'] ?? '' }}" class="mt-1 w-full rounded-md border-ink-200 text-sm" placeholder="{{ $filterOptions['rent_max'] ? ceil((float) $filterOptions['rent_max']) : '0' }}">
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Ordenação</span>
                    <select name="sort" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                        <option value="published_desc" @selected(($filters['sort'] ?? 'published_desc') === 'published_desc')>Mais recentes</option>
                        <option value="rent_asc" @selected(($filters['sort'] ?? '') === 'rent_asc')>Renda crescente</option>
                        <option value="rent_desc" @selected(($filters['sort'] ?? '') === 'rent_desc')>Renda decrescente</option>
                        <option value="typology" @selected(($filters['sort'] ?? '') === 'typology')>Tipologia</option>
                    </select>
                </label>

                <div class="flex items-end gap-3">
                    <button class="mv-button-primary w-full justify-center">Filtrar</button>
                    <a href="{{ route('public.housing-units.index') }}" class="mv-button-secondary whitespace-nowrap">Limpar</a>
                </div>
            </div>
        </form>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($housingUnits as $housingUnit)
                <x-public-housing-unit-card :housing-unit="$housingUnit" />
            @empty
                <div class="rounded-md border border-ink-100 bg-white p-8 text-center text-sm text-ink-500 md:col-span-2 xl:col-span-3">Não existem habitações públicas com estes filtros.</div>
            @endforelse
        </div>

        <div class="mt-8">{{ $housingUnits->links() }}</div>
    </section>
</x-public-layout>
