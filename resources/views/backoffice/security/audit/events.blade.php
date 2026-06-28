<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Auditoria imutável</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <section class="mv-surface overflow-hidden">
            <table class="mv-table">
                <thead><tr><th>Data</th><th>Evento</th><th>Categoria</th><th>Severidade</th><th>Ator</th><th></th></tr></thead>
                <tbody>@foreach ($events as $event)<tr><td>{{ $event->occurred_at?->format('d/m/Y H:i:s') }}</td><td>{{ $event->event_code }}</td><td>{{ $event->category?->label() }}</td><td>{{ $event->severity?->label() }}</td><td>{{ $event->user?->name ?? 'Sistema' }}</td><td><a class="mv-link" href="{{ route('backoffice.security.audit.events.show', $event) }}">Ver</a></td></tr>@endforeach</tbody>
            </table>
            <div class="p-4">{{ $events->links() }}</div>
        </section>
    </div></div>
</x-app-layout>
