<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Auditoria de acessos</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface overflow-hidden">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Actor</th>
                            <th>Alvo</th>
                            <th>Role</th>
                            <th>Equipa</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->event_code }}</td>
                                <td>{{ $event->actor?->name ?? 'Sistema' }}</td>
                                <td>{{ $event->targetUser?->name ?? '-' }}</td>
                                <td>{{ $event->role?->label ?? '-' }}</td>
                                <td>{{ $event->municipalTeam?->name ?? '-' }}</td>
                                <td>{{ $event->occurred_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-ink-500">Sem eventos de alteração de acesso.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $events->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
