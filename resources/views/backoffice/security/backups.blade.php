<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Revisões de backup e restore</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.security.backups.store') }}" class="mv-surface grid gap-4 p-5 md:grid-cols-2">
            @csrf
            <input name="environment" class="mv-input" value="pre-production"><input name="frequency" class="mv-input" placeholder="Frequência">
            <input name="retention_period" class="mv-input" placeholder="Retenção"><textarea name="findings" class="mv-input" rows="2" placeholder="Observações"></textarea>
            <button class="mv-button-primary w-fit">Registar revisão</button>
        </form>
        <section class="mv-surface overflow-hidden"><table class="mv-table"><thead><tr><th>Número</th><th>Estado</th><th>Ambiente</th><th>Revisto em</th></tr></thead><tbody>@foreach ($reviews as $review)<tr><td>{{ $review->review_number }}</td><td>{{ $review->status?->label() }}</td><td>{{ $review->environment }}</td><td>{{ $review->reviewed_at?->format('d/m/Y H:i') }}</td></tr>@endforeach</tbody></table><div class="p-4">{{ $reviews->links() }}</div></section>
    </div></div>
</x-app-layout>
