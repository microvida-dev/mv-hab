<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Análise administrativa</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $review->review_type->label() }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface p-6">
                <p class="text-sm text-ink-500">Estado</p>
                <p class="mt-1 font-semibold text-ink-900">{{ $review->status->label() }} · {{ $review->result?->label() ?? 'Sem resultado' }}</p>
                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $review->summary }}</p>
            </section>
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Itens analisados</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($review->items as $item)
                        <div class="py-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $item->name }}</p>
                            <p class="mt-1 text-ink-600">{{ $item->message }}</p>
                            <p class="mt-1 text-xs text-ink-500">{{ $item->result->label() }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            <form method="POST" action="{{ route('backoffice.application-reviews.complete', $review) }}" class="mv-surface space-y-4 p-6">
                @csrf
                <h2 class="text-lg font-semibold text-ink-900">Concluir análise</h2>
                <select name="result" class="w-full rounded-md border-ink-300 text-sm">
                    @foreach ($results as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <textarea name="summary" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Resumo final">{{ $review->summary }}</textarea>
                <button class="mv-button-primary">Concluir</button>
            </form>
        </div>
    </div>
</x-app-layout>
