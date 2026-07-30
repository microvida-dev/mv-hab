<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Candidaturas</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                Análise progressiva em bloco
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Selecione o concurso cuja análise documental pretende acompanhar.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($contests as $contest)
                    <article class="mv-surface flex flex-col justify-between p-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                                {{ $contest->code }}
                            </p>
                            <h2 class="mt-2 text-lg font-semibold text-ink-900">
                                {{ $contest->title }}
                            </h2>
                            <p class="mt-2 text-sm text-ink-500">
                                {{ $contest->processes_count }} processo(s) administrativo(s)
                            </p>
                        </div>

                        <a
                            href="{{ route('backoffice.application-review-workspace.show', $contest) }}"
                            class="mv-button-primary mt-5 justify-center"
                        >
                            Abrir mesa de análise
                        </a>
                    </article>
                @empty
                    <div class="mv-surface col-span-full px-6 py-12 text-center">
                        <p class="font-semibold text-ink-900">
                            Sem concursos disponíveis
                        </p>
                        <p class="mt-1 text-sm text-ink-500">
                            Não existem concursos com acesso municipal para apresentar.
                        </p>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
