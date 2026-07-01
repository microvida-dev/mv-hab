<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Perfis e permissões</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @error('access')
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <section class="mv-surface overflow-hidden">
                <table class="mv-table">
                    <thead><tr><th>Role</th><th>Utilizadores</th><th>Permissões</th></tr></thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ $role->label }}</p>
                                    <p class="text-xs text-ink-500">{{ $role->name }}</p>
                                </td>
                                <td>{{ $role->users_count }}</td>
                                <td>{{ $role->permissions->pluck('name')->sort()->take(12)->join(', ') }}{{ $role->permissions->count() > 12 ? '...' : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            @if ($users->isNotEmpty())
                <section class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Alteração rápida de role</h2>
                    <div class="mt-4 grid gap-6 xl:grid-cols-2">
                        <form method="POST" action="{{ route('backoffice.users.roles.assign', $users->first()) }}" class="grid gap-3" onsubmit="this.action=this.action.replace(/users\\/\\d+/, 'users/' + this.user_id.value)">
                        @csrf
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium text-ink-700">Utilizador</span>
                            <select name="user_id" class="mv-input" required>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium text-ink-700">Role</span>
                            <select name="role" class="mv-input" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <input name="justification" class="rounded-2xl border-ink-200 text-sm" placeholder="Justificação" required>
                        <button class="mv-button-primary">Atribuir</button>
                        </form>

                        <form method="POST" action="{{ route('backoffice.users.roles.remove', $users->first()) }}" class="grid gap-3" onsubmit="this.action=this.action.replace(/users\\/\\d+/, 'users/' + this.user_id.value)">
                        @csrf
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium text-ink-700">Utilizador</span>
                            <select name="user_id" class="mv-input" required>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium text-ink-700">Role</span>
                            <select name="role" class="mv-input" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <input name="justification" class="rounded-2xl border-ink-200 text-sm" placeholder="Justificação" required>
                        <button class="mv-button-danger">Remover</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
