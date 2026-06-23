<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Revisões de permissões</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <form method="POST" action="{{ route('backoffice.security.permission-reviews.store') }}" class="mv-surface flex flex-wrap items-end gap-4 p-5">
                @csrf
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Âmbito</span>
                    <input name="scope" value="all" class="mv-input">
                </label>
                <button class="mv-button-primary">Iniciar revisão</button>
            </form>

            <section class="mv-surface overflow-hidden">
                <table class="mv-table">
                    <thead><tr><th>Número</th><th>Estado</th><th>Âmbito</th><th>Iniciada</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td>{{ $review->review_number }}</td>
                                <td>{{ $review->status }}</td>
                                <td>{{ $review->scope }}</td>
                                <td>{{ $review->started_at?->format('d/m/Y H:i') }}</td>
                                <td><a class="mv-link" href="{{ route('backoffice.security.permission-reviews.show', $review) }}">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">{{ $reviews->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
