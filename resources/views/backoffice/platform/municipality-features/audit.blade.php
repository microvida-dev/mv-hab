<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Auditoria de funcionalidades"
            :title="$municipality->name"
            description="Histórico imutável de ativações e desativações municipais."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.platform.municipality-features.show', $municipality) }}" class="mv-button-secondary">Voltar à configuração</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Funcionalidade</th>
                                <th>Alteração</th>
                                <th>Operador</th>
                                <th>Justificação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr>
                                    <td>{{ $event->occurred_at?->format('d/m/Y H:i') }}</td>
                                    <td class="font-mono text-xs">{{ data_get($event->metadata, 'feature_key') }}</td>
                                    <td>
                                        <x-mv.badge :tone="data_get($event->metadata, 'after') ? 'success' : 'neutral'">
                                            {{ data_get($event->metadata, 'after') ? 'Ativada' : 'Desativada' }}
                                        </x-mv.badge>
                                    </td>
                                    <td>{{ $event->user?->name ?? 'Sistema' }}</td>
                                    <td>{{ data_get($event->metadata, 'justification') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-ink-500">Ainda não existem alterações auditadas.</td>
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
