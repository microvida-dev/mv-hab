<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Candidaturas</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Publicações coletivas da revisão documental</h1>
            <p class="mt-1 text-sm text-ink-500">Histórico imutável das publicações efetuadas a partir de lotes selados.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @forelse ($publications as $publication)
                <article class="mv-surface p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $publication->contest->title }}</p>
                            <p class="mt-1 text-sm text-ink-500">
                                {{ $publication->cycle->label() }} · {{ $publication->published_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <x-mv.badge tone="success">{{ $publication->status->label() }}</x-mv.badge>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-ink-400">Resultados</dt><dd class="mt-1 font-semibold text-ink-800">{{ $publication->results_count }}</dd></div>
                        <div><dt class="text-ink-400">Lote</dt><dd class="mt-1 font-semibold text-ink-800">{{ $publication->batch->sequence_number }}</dd></div>
                        <div><dt class="text-ink-400">Publicada por</dt><dd class="mt-1 font-semibold text-ink-800">{{ $publication->publishedBy?->name ?? 'Utilizador removido' }}</dd></div>
                    </dl>
                    <a href="{{ route('backoffice.application-review-publications.show', $publication) }}" class="mv-button-secondary mt-4">Consultar publicação</a>
                </article>
            @empty
                <div class="mv-surface p-10 text-center text-sm text-ink-500">Ainda não existem publicações coletivas.</div>
            @endforelse
            {{ $publications->links() }}
        </div>
    </div>
</x-app-layout>
