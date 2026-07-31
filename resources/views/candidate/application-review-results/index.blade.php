<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Processo</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Resultados da revisão documental</h1>
            <p class="mt-1 text-sm text-ink-500">Consulte apenas os resultados oficialmente publicados das suas candidaturas.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            @forelse ($results as $result)
                <article class="mv-surface p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $result->publication->contest->title }}</p>
                            <p class="mt-1 text-sm text-ink-500">Processo {{ $result->process_number }} · {{ $result->published_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <x-mv.badge :tone="$result->outcome->value === 'correction_required' ? 'warning' : 'success'">{{ $result->outcome->label() }}</x-mv.badge>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-ink-700">{{ $result->result_payload['message'] }}</p>
                    <a href="{{ route('candidate.application-review-results.show', $result) }}" class="mv-button-secondary mt-4">Consultar detalhe</a>
                </article>
            @empty
                <div class="mv-surface p-10 text-center text-sm text-ink-500">Ainda não existem resultados de revisão documental publicados.</div>
            @endforelse
            {{ $results->links() }}
        </div>
    </div>
</x-app-layout>
