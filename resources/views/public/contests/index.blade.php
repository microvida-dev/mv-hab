<x-public-layout title="Concursos" description="Concursos municipais publicados com oferta habitacional e prazos de candidatura.">
    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('public.housing-offer.index') }}" class="text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">Oferta habitacional</a>
            <p class="text-sm font-semibold text-mvhab-primary">Oportunidades publicadas</p>
            <h1 class="mt-2 text-3xl font-semibold text-ink-900">Concursos de Arrendamento Acessível</h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-ink-500">Consulte os avisos publicados, prazos de candidatura e informação necessária para preparar a sua participação.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('public.contests.index') }}" class="mv-surface mb-8 grid gap-4 p-5 md:grid-cols-[minmax(0,1fr)_16rem_auto]">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="mv-input text-sm" placeholder="Pesquisar por título, código ou resumo">
            <select name="status" class="mv-input text-sm">
                <option value="">Todos os estados</option>
                @foreach (($statuses ?? []) as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="mv-button-primary">Filtrar</button>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($contests as $contest)
                <x-public-contest-card :contest="$contest" />
            @empty
                <div class="mv-surface col-span-full p-8 text-center text-sm text-ink-500">Não existem concursos publicados neste momento.</div>
            @endforelse
        </div>

        <div class="mt-8">{{ $contests->links() }}</div>
    </section>
</x-public-layout>
