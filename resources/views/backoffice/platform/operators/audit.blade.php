<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Administração da plataforma"
            title="Auditoria de operadores"
            description="Histórico imutável de bootstrap, concessões e revogações."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.platform.operators.index') }}" class="mv-button-secondary">Voltar aos operadores</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Evento</th>
                                <th>Utilizador alvo</th>
                                <th>Operador</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr>
                                    <td>{{ $event->occurred_at?->format('d/m/Y H:i') }}</td>
                                    <td class="font-mono text-xs">{{ $event->event_code }}</td>
                                    <td>
                                        {{ $event->subjectUser?->name ?? 'Conta removida' }}
                                        <span class="text-xs text-ink-500">#{{ $event->subject_user_id }}</span>
                                    </td>
                                    <td>{{ $event->user?->name ?? 'Bootstrap externo' }}</td>
                                    <td>
                                        <x-mv.badge :tone="data_get($event->new_values, 'status') === 'active' ? 'success' : 'neutral'">
                                            {{ data_get($event->new_values, 'status') === 'active' ? 'Ativo' : 'Revogado' }}
                                        </x-mv.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-ink-500">
                                        Ainda não existem eventos de operadores de plataforma.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $events->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
