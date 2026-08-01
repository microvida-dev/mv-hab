<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Publicação coletiva</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $publication->contest->title }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $publication->cycle->label() }} · {{ $publication->published_at->format('d/m/Y H:i') }}</p>
            </div>
            <a href="{{ route('backoffice.application-review-publications.index') }}" class="mv-button-secondary">Histórico</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="grid gap-4 md:grid-cols-4">
                <x-mv.stat-card label="Estado" :value="$publication->status->label()" />
                <x-mv.stat-card label="Resultados" :value="$publication->item_count" />
                <x-mv.stat-card label="Publicada por" :value="$publication->publishedBy?->name ?? 'Utilizador removido'" />
                <x-mv.stat-card label="Lote" :value="$publication->batch->sequence_number" />
            </section>
            <x-mv.alert tone="info" title="Instante oficial comum">
                Todos os resultados ficaram disponíveis em {{ $publication->published_at->format('d/m/Y H:i:s') }}. A entrega externa é assíncrona e não altera este instante.
            </x-mv.alert>
            <section class="mv-surface p-5">
                <p class="text-sm font-semibold text-ink-900">Fundamento</p>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-600">{{ $publication->reason }}</p>
                <p class="mt-4 break-all font-mono text-xs text-ink-500">{{ $publication->publication_hash }}</p>
            </section>
            <section class="space-y-3">
                @foreach ($publication->results as $result)
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $result->process_number }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $result->application_number ?? $result->application_public_id }}</p>
                            </div>
                            <x-mv.badge :tone="match ($result->outcome->value) {
                                'correction_required' => 'warning',
                                'correction_rejected' => 'danger',
                                default => 'success',
                            }">{{ $result->outcome->label() }}</x-mv.badge>
                        </div>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                            <div><dt class="text-ink-400">Área pessoal</dt><dd class="mt-1 font-semibold text-ink-800">{{ $result->inAppDelivery->status->label() }}</dd></div>
                            <div><dt class="text-ink-400">Email</dt><dd class="mt-1 font-semibold text-ink-800">{{ $result->emailDelivery->status->label() }}</dd></div>
                            <div><dt class="text-ink-400">Notificação</dt><dd class="mt-1 font-semibold text-ink-800">{{ $result->officialNotification->status->label() }}</dd></div>
                            <div><dt class="text-ink-400">Hash</dt><dd class="mt-1 break-all font-mono text-xs text-ink-600">{{ $result->result_hash }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
