<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamações"
            title="Reclamações submetidas"
            description="Acompanhe reclamações apresentadas por candidatos durante os períodos de audiência e reclamação."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Reclamações" padding="p-0" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-4 py-3">Número</th>
                                <th class="px-4 py-3">Candidato</th>
                                <th class="px-4 py-3">Lista</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Submissão</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($complaints as $complaint)
                                <tr>
                                    <td class="px-4 py-3 font-semibold">{{ $complaint->complaint_number }}</td>
                                    <td class="px-4 py-3">{{ $complaint->candidate?->name }}</td>
                                    <td class="px-4 py-3">{{ $complaint->provisionalList?->list_number }}</td>
                                    <td class="px-4 py-3"><x-mv.badge>{{ $complaint->status->label() }}</x-mv.badge></td>
                                    <td class="px-4 py-3">{{ $complaint->submitted_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="font-semibold text-civic-700" href="{{ route('backoffice.complaints.show', $complaint) }}">Abrir</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-500">Sem reclamações.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-mv.section>

            <div class="mt-4">
                {{ $complaints->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
