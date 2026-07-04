<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Procedimento"
            title="Confirmações de processo"
            description="Confirmações processuais geradas para rastreabilidade administrativa."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Confirmações" padding="p-0" class="overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-4 py-3">Confirmação</th>
                            <th class="px-4 py-3">Processo</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Criada em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($confirmations as $confirmation)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $confirmation->confirmation_number }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $confirmation->process_number }}</td>
                                <td class="px-4 py-3"><x-mv.badge>{{ $confirmation->status->label() }}</x-mv.badge></td>
                                <td class="px-4 py-3">{{ $confirmation->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a class="font-semibold text-civic-700" href="{{ route('backoffice.process-confirmations.show', $confirmation) }}">Abrir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-ink-500">Sem confirmações geradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-mv.section>

            {{ $confirmations->links() }}
        </div>
    </div>
</x-app-layout>
