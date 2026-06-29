@props(['programs'])

<section class="border-y border-ink-100 bg-ink-50">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-civic-700">Programas</p>
            <h2 class="mt-2 text-3xl font-bold text-ink-900">Enquadramento municipal</h2>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-3">
            @forelse ($programs as $program)
                <article class="mv-surface p-6">
                    <p class="text-xs font-semibold text-ink-500">{{ $program->municipality->name }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-ink-900">{{ $program->name }}</h3>
                    <p class="mt-3 text-sm leading-6 text-ink-500">{{ $program->summary }}</p>
                    <p class="mt-5 text-sm font-semibold text-ink-700">
                        {{ $program->contests_count }} concurso(s) publicado(s)
                    </p>
                    <a href="{{ route('public.programs.show', $program->slug) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-civic-700 hover:text-civic-900">
                        Consultar programa
                        <x-ui-icon name="arrow" class="h-4 w-4" />
                    </a>
                </article>
            @empty
                <div class="mv-surface col-span-full p-8 text-center text-sm text-ink-500">
                    Não existem programas publicados.
                </div>
            @endforelse
        </div>
    </div>
</section>
