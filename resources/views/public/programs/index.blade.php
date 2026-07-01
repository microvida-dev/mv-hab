<x-public-layout title="Programas">
    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container">
            <div class="max-w-3xl">
                <p class="mv-caption">Informação pública</p>

                <h1 class="mv-heading mt-3">
                    Programas de Arrendamento Acessível
                </h1>

                <p class="mv-description mt-6">
                    Conheça os programas municipais publicados, respetivas regras gerais e concursos associados.
                </p>
            </div>
        </div>
    </section>

    <section class="mv-section bg-mvhab-surface">
        <div class="mv-container">
            <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($programs as $program)
                    <article class="mv-card-interactive flex h-full flex-col p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                                <x-mv-icon name="program" size="lg" />
                            </div>

                            <span class="mv-badge mv-badge-civic">
                                Publicado
                            </span>
                        </div>

                        <div class="mt-6 flex-1">
                            <p class="mv-data-label">
                                {{ $program->municipality->name }}
                            </p>

                            <h2 class="mv-card-title mt-2">
                                {{ $program->name }}
                            </h2>

                            <p class="mv-section-description mt-3">
                                {{ $program->summary }}
                            </p>

                            <div class="mt-6 grid gap-3 border-t border-ink-100 pt-5">
                                @if ($program->starts_at || $program->ends_at)
                                    <div class="flex items-start gap-3">
                                        <x-mv-icon name="calendar" size="sm" class="mt-0.5 text-mvhab-primary" />

                                        <div>
                                            <p class="mv-data-label">Período</p>
                                            <p class="mv-data-value">
                                                {{ $program->starts_at?->format('d/m/Y') ?? 'Sem início definido' }}
                                                —
                                                {{ $program->ends_at?->format('d/m/Y') ?? 'Sem fim definido' }}
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-start gap-3">
                                    <x-mv-icon name="contest" size="sm" class="mt-0.5 text-mvhab-primary" />

                                    <div>
                                        <p class="mv-data-label">Concursos</p>
                                        <p class="mv-data-value">
                                            {{ $program->contests_count }} concurso(s) publicado(s)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a
                            href="{{ route('public.programs.show', $program->slug) }}"
                            class="mv-button-secondary mt-6 justify-center"
                        >
                            Ver programa
                            <x-mv-icon name="arrow-right" size="sm" />
                        </a>
                    </article>
                @empty
                    <div class="mv-card col-span-full py-16 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-mvhab-surface">
                            <x-mv-icon name="program" size="xl" class="text-mvhab-primary" />
                        </div>

                        <h2 class="mt-8 text-xl font-semibold text-ink-900">
                            Não existem programas publicados
                        </h2>

                        <p class="mv-description mx-auto mt-4 max-w-xl">
                            De momento não existem programas municipais publicados. Consulte esta página regularmente para acompanhar novas oportunidades.
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-10">
                {{ $programs->links() }}
            </div>
        </div>
    </section>
</x-public-layout>
