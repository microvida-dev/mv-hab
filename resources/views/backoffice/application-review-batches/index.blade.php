<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Fecho coletivo</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                Lotes de revisão
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Selecione um concurso para validar e selar o snapshot técnico de toda a fase.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 lg:grid-cols-2">
                @forelse ($contests as $entry)
                    @php
                        $contest = $entry['contest'];
                        $nextCycle = $entry['next_cycle'];
                    @endphp
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                                    {{ $contest->code }}
                                </p>
                                <h2 class="mt-1 text-lg font-semibold text-ink-900">
                                    {{ $contest->title }}
                                </h2>
                                <p class="mt-2 text-sm text-ink-500">
                                    {{ $entry['process_count'] }} processo(s) ·
                                    {{ $entry['batch_count'] }} lote(s) selado(s)
                                </p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ $nextCycle?->label() ?? 'Ciclos concluídos' }}
                            </span>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <a
                                href="{{ route('backoffice.application-review-batches.contest', $contest) }}"
                                class="mv-button-primary"
                            >
                                Abrir fecho coletivo
                            </a>
                        </div>
                    </article>
                @empty
                    <x-mv.empty-state
                        title="Sem concursos disponíveis"
                        description="Não existem concursos acessíveis para preparar lotes de revisão."
                    />
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
