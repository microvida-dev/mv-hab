<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-civic-700">Equipa municipal</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $team->name }}</h1>
            </div>
            <a href="{{ route('backoffice.teams.edit', $team) }}" class="mv-button-secondary">Editar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @error('access')
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <section class="grid gap-6 xl:grid-cols-3">
                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Estado</h2>
                    <p class="mt-3 text-sm text-ink-700">{{ $team->status === 'active' ? 'Ativa' : 'Inativa' }}</p>
                </div>
                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Responsável</h2>
                    <p class="mt-3 text-sm text-ink-700">{{ $team->manager?->name ?? '-' }}</p>
                </div>
                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Escopos</h2>
                    <p class="mt-3 text-sm text-ink-700">{{ collect($team->functional_scopes ?? [])->join(', ') ?: '-' }}</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 p-5">
                        <h2 class="text-lg font-semibold text-ink-900">Membros</h2>
                    </div>
                    <table class="mv-table">
                        <thead><tr><th>Utilizador</th><th>Role</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($team->members as $member)
                                <tr>
                                    <td>{{ $member->name }}</td>
                                    <td>{{ $member->roles->pluck('label')->join(', ') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('backoffice.teams.members.remove', $team) }}" class="flex gap-2">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $member->id }}">
                                            <input name="justification" class="w-48 rounded-md border-ink-200 text-sm" placeholder="Justificação" required>
                                            <button class="mv-button-danger">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-ink-500">Sem membros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('backoffice.teams.members.store', $team) }}" class="mv-surface grid gap-4 p-5">
                    @csrf
                    <h2 class="text-lg font-semibold text-ink-900">Adicionar membro</h2>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Utilizador</span>
                        <select name="user_id" class="rounded-md border-ink-200" required>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Função na equipa</span>
                        <input name="role_in_team" class="rounded-md border-ink-200">
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Justificação</span>
                        <textarea name="justification" rows="3" class="rounded-md border-ink-200" required></textarea>
                    </label>
                    <button class="mv-button-primary">Adicionar</button>
                </form>
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="border-b border-ink-100 p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Histórico da equipa</h2>
                </div>
                <table class="mv-table">
                    <thead><tr><th>Evento</th><th>Actor</th><th>Utilizador</th><th>Data</th></tr></thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->event_code }}</td>
                                <td>{{ $event->actor?->name ?? 'Sistema' }}</td>
                                <td>{{ $event->targetUser?->name ?? '-' }}</td>
                                <td>{{ $event->occurred_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-ink-500">Sem histórico.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $events->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
