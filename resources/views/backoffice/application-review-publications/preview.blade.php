<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Confirmação</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Pré-visualização da publicação coletiva</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $preview['batch']->contest->title }} · {{ $preview['batch']->cycle->label() }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.alert tone="warning" title="Confirme o conjunto integral">
                A publicação criará {{ $preview['item_count'] }} resultados privados e as respetivas entregas duráveis. O envio externo será processado depois do commit.
            </x-mv.alert>
            <section class="mv-surface p-5">
                <p class="text-sm font-semibold text-ink-900">Fundamento</p>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-600">{{ $preview['reason'] }}</p>
                <p class="mt-4 break-all font-mono text-xs text-ink-500">Hash: {{ $preview['publication_hash'] }}</p>
            </section>
            <section class="space-y-3">
                @foreach ($preview['items'] as $item)
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $item['process_number'] }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $item['application_number'] ?? 'Sem número público' }}</p>
                            </div>
                            <x-mv.badge :tone="match ($item['outcome']->value) {
                                'correction_required' => 'warning',
                                'correction_rejected' => 'danger',
                                default => 'success',
                            }">{{ $item['outcome_label'] }}</x-mv.badge>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-ink-700">{{ $item['message'] }}</p>
                    </article>
                @endforeach
            </section>
            <form method="POST" action="{{ route('backoffice.application-review-publications.publish', $preview['batch']) }}" class="flex flex-wrap gap-3">
                @csrf
                <input type="hidden" name="reason" value="{{ $preview['reason'] }}">
                <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                <button class="mv-button-primary">Publicar todos os resultados</button>
                <a href="{{ route('backoffice.application-review-publications.create', $preview['batch']) }}" class="mv-button-secondary">Rever fundamento</a>
            </form>
        </div>
    </div>
</x-app-layout>
