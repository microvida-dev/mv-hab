<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiências"
            title="Audiência de interessados"
            description="Gira audiências e prazos de pronúncia associados aos processos administrativos."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.hearings.create') }}" class="mv-button-primary">Criar audiência</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Audiências" padding="p-0" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-4 py-3">Número</th>
                                <th class="px-4 py-3">Candidato</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($hearings as $hearing)
                                <tr>
                                    <td class="px-4 py-3 font-semibold">{{ $hearing->hearing_number }}</td>
                                    <td class="px-4 py-3">{{ $hearing->candidate?->name }}</td>
                                    <td class="px-4 py-3">{{ $hearing->hearing_type->label() }}</td>
                                    <td class="px-4 py-3"><x-mv.badge>{{ $hearing->status->label() }}</x-mv.badge></td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.hearings.show', $hearing) }}">Abrir</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-ink-500">Sem audiências.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-mv.section>

            <div class="mt-4">
                {{ $hearings->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
