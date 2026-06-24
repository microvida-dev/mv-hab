<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-civic-700">QA-30</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Utilizadores</h1>
            </div>
            <a href="{{ route('backoffice.users.create') }}" class="mv-button-primary">Criar utilizador</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @error('access')
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <form method="GET" action="{{ route('backoffice.users.index') }}" class="mv-surface grid gap-4 p-5 md:grid-cols-4">
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Pesquisa</span>
                    <input name="q" value="{{ request('q') }}" class="mv-input rounded-md border-ink-200">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Role</span>
                    <select name="role" class="mv-input rounded-md border-ink-200">
                        <option value="">Todas</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Equipa</span>
                    <select name="team" class="mv-input rounded-md border-ink-200">
                        <option value="">Todas</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected((string) request('team') === (string) $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Estado</span>
                    <select name="status" class="mv-input rounded-md border-ink-200">
                        <option value="">Todos</option>
                        <option value="active" @selected(request('status') === 'active')>Ativo</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inativo</option>
                    </select>
                </label>
                <div class="md:col-span-4">
                    <button class="mv-button-secondary">Filtrar</button>
                </div>
            </form>

            <section class="mv-surface overflow-hidden">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Roles</th>
                            <th>Equipas</th>
                            <th>Estado</th>
                            <th>MFA</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ $user->name }}</p>
                                    <p class="text-xs text-ink-500">{{ $user->email }}</p>
                                </td>
                                <td>{{ $user->roles->pluck('label')->join(', ') ?: 'Sem role' }}</td>
                                <td>{{ $user->municipalTeams->pluck('name')->join(', ') ?: 'Sem equipa' }}</td>
                                <td>{{ $user->status === 'active' ? 'Ativo' : 'Inativo' }}</td>
                                <td>{{ $user->mfa_required ? 'Obrigatório' : 'Não imposto' }}</td>
                                <td class="text-right"><a class="mv-link" href="{{ route('backoffice.users.show', $user) }}">Ver</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-ink-500">Sem utilizadores para os filtros atuais.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $users->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
