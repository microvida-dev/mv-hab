<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $review->review_number }}</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface p-5">
                <p class="text-sm text-ink-500">Estado: {{ $review->status }} · Âmbito: {{ $review->scope }} · Itens: {{ $review->items->count() }}</p>
                <p class="mt-2 text-sm text-ink-700">{{ $review->summary }}</p>
                @if (! $review->completed_at)
                    <form method="POST" action="{{ route('backoffice.security.permission-reviews.complete', $review) }}" class="mt-4 grid gap-3">
                        @csrf
                        <textarea name="summary" class="mv-input" rows="3">{{ $review->summary }}</textarea>
                        <button class="mv-button-primary w-fit">Concluir revisão</button>
                    </form>
                @endif
            </section>

            <section class="mv-surface overflow-hidden">
                <table class="mv-table">
                    <thead><tr><th>Risco</th><th>Utilizador/Role</th><th>Módulo</th><th>Finding</th><th>Recomendação</th></tr></thead>
                    <tbody>
                        @foreach ($review->items as $item)
                            <tr>
                                <td>{{ $item->risk_level }}</td>
                                <td>{{ $item->user?->name ?? $item->role_name }}</td>
                                <td>{{ $item->module }}</td>
                                <td>{{ $item->finding }}</td>
                                <td>{{ $item->recommendation }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
