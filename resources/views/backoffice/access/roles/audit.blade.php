<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Auditoria de acessos"
            :title="$role->label"
            description="Histórico imutável das alterações ao perfil."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.roles.show', $role) }}" class="mv-button-secondary">Voltar ao perfil</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <thead><tr><th>Evento</th><th>Operador</th><th>Justificação</th><th>Data</th></tr></thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr>
                                    <td class="font-medium text-ink-900">{{ $event->event_code }}</td>
                                    <td>{{ $event->actor?->name ?? 'Sistema' }}</td>
                                    <td>{{ $event->justification }}</td>
                                    <td>{{ $event->occurred_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Sem eventos registados para este perfil.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $events->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
