<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Gestão de acessos</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $user->name }}</h1>
            </div>
            <a href="{{ route('backoffice.users.edit', $user) }}" class="mv-button-secondary">Editar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @error('access')
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <section class="grid gap-6 xl:grid-cols-3">
                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Conta</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="font-medium text-ink-500">Email</dt><dd class="text-ink-900">{{ $user->email }}</dd></div>
                        <div><dt class="font-medium text-ink-500">Estado</dt><dd class="text-ink-900">{{ $user->status }}</dd></div>
                        <div><dt class="font-medium text-ink-500">MFA</dt><dd class="text-ink-900">{{ $user->mfa_required ? 'Obrigatório' : 'Não imposto' }}</dd></div>
                    </dl>
                </div>
                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Perfis</h2>
                    <p class="mt-3 text-sm text-ink-700">{{ $user->roles->pluck('label')->join(', ') ?: 'Sem role' }}</p>
                </div>
                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Equipas</h2>
                    <p class="mt-3 text-sm text-ink-700">{{ $user->municipalTeams->pluck('name')->join(', ') ?: 'Sem equipa' }}</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Ações de conta</h2>
                    <div class="mt-4 grid gap-3">
                        <form method="POST" action="{{ route('backoffice.users.force-mfa', $user) }}" class="grid gap-2">
                            @csrf
                            <input name="justification" class="mv-input" placeholder="Justificação" required>
                            <button class="mv-button-secondary">Forçar MFA</button>
                        </form>
                        <form method="POST" action="{{ route('backoffice.users.reset-password', $user) }}" class="grid gap-2">
                            @csrf
                            <input name="justification" class="mv-input" placeholder="Justificação" required>
                            <button class="mv-button-secondary">Enviar reset seguro</button>
                        </form>
                        @if ($user->status === 'active')
                            <form method="POST" action="{{ route('backoffice.users.deactivate', $user) }}" class="grid gap-2">
                                @csrf
                                <input name="justification" class="mv-input" placeholder="Justificação" required>
                                <button class="mv-button-danger">Desativar</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('backoffice.users.reactivate', $user) }}" class="grid gap-2">
                                @csrf
                                <input name="justification" class="mv-input" placeholder="Justificação" required>
                                <button class="mv-button-primary">Reativar</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Alterar roles</h2>
                    <form method="POST" action="{{ route('backoffice.users.roles.assign', $user) }}" class="mt-4 grid gap-3">
                        @csrf
                        <select name="role" class="mv-input" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->label }}</option>
                            @endforeach
                        </select>
                        <input name="justification" class="mv-input" placeholder="Justificação" required>
                        <button class="mv-button-primary">Atribuir role</button>
                    </form>
                    <form method="POST" action="{{ route('backoffice.users.roles.remove', $user) }}" class="mt-4 grid gap-3">
                        @csrf
                        <select name="role" class="mv-input" required>
                            @foreach ($user->roles as $role)
                                <option value="{{ $role->name }}">{{ $role->label }}</option>
                            @endforeach
                        </select>
                        <input name="justification" class="mv-input" placeholder="Justificação" required>
                        <button class="mv-button-danger">Remover role</button>
                    </form>
                </div>
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="border-b border-ink-100 p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Histórico de acessos</h2>
                </div>
                <table class="mv-table">
                    <thead><tr><th>Evento</th><th>Actor</th><th>Role/Equipa</th><th>Data</th></tr></thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->event_code }}</td>
                                <td>{{ $event->actor?->name ?? 'Sistema' }}</td>
                                <td>{{ $event->role?->label ?? $event->municipalTeam?->name ?? '-' }}</td>
                                <td>{{ $event->occurred_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-ink-500">Sem alterações de acesso registadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $events->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
