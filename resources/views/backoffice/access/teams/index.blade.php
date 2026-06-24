<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold text-ink-900">Equipas municipais</h1>
            <a href="{{ route('backoffice.teams.create') }}" class="mv-button-primary">Criar equipa</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @error('access')
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <section class="mv-surface overflow-hidden">
                <table class="mv-table">
                    <thead><tr><th>Equipa</th><th>Responsável</th><th>Estado</th><th>Membros</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($teams as $team)
                            <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ $team->name }}</p>
                                    <p class="text-xs text-ink-500">{{ collect($team->functional_scopes ?? [])->join(', ') }}</p>
                                </td>
                                <td>{{ $team->manager?->name ?? '-' }}</td>
                                <td>{{ $team->status === 'active' ? 'Ativa' : 'Inativa' }}</td>
                                <td>{{ $team->members_count }}</td>
                                <td class="text-right"><a class="mv-link" href="{{ route('backoffice.teams.show', $team) }}">Ver</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-ink-500">Sem equipas registadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $teams->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
