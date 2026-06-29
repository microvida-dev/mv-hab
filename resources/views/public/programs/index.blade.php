<x-public-layout title="Programas">
    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-mvhab-primary">Informação pública</p>
            <h1 class="mt-2 text-3xl font-semibold text-ink-900">Programas de Arrendamento Acessível</h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-ink-500">Conheça os programas municipais publicados, respetivas regras gerais e concursos associados.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($programs as $program)
                <article class="mv-surface flex flex-col p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-semibold text-ink-500">{{ $program->municipality->name }}</p>
                        <span class="rounded-md bg-mvhab-surface px-2.5 py-1 text-xs font-semibold text-mvhab-primary">Publicado</span>
                    </div>
                    <h2 class="mt-2 text-xl font-semibold text-ink-900">{{ $program->name }}</h2>
                    <p class="mt-3 flex-1 text-sm leading-6 text-ink-500">{{ $program->summary }}</p>
                    @if ($program->starts_at || $program->ends_at)
                        <p class="mt-4 text-sm text-ink-600">
                            {{ $program->starts_at?->format('d/m/Y') ?? 'Sem início definido' }}
                            a
                            {{ $program->ends_at?->format('d/m/Y') ?? 'Sem fim definido' }}
                        </p>
                    @endif
                    <p class="mt-4 text-sm font-semibold text-ink-700">{{ $program->contests_count }} concurso(s) publicado(s)</p>
                    <a href="{{ route('public.programs.show', $program->slug) }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">
                        Ver programa
                        <x-ui-icon name="arrow" class="h-4 w-4" />
                    </a>
                </article>
            @empty
                <div class="mv-surface col-span-full p-8 text-center">
                    <p class="font-semibold text-ink-900">De momento não existem programas publicados.</p>
                    <p class="mt-2 text-sm text-ink-500">Consulte esta página regularmente para acompanhar novas oportunidades.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $programs->links() }}</div>
    </section>
</x-public-layout>
