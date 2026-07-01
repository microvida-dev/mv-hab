<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Criar utilizador</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @error('access')
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('backoffice.users.store') }}" class="mv-surface grid gap-4 p-6">
                @csrf
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Nome</span>
                    <input name="name" value="{{ old('name') }}" class="mv-input" required>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="mv-input" required>
                </label>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Role inicial</span>
                        <select name="role" class="mv-input" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Equipa</span>
                        <select name="team_id" class="mv-input">
                            <option value="">Sem equipa</option>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" @selected((string) old('team_id') === (string) $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Estado</span>
                        <select name="status" class="mv-input" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>Ativo</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inativo</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium text-ink-700">
                        <input type="checkbox" name="mfa_required" value="1" @checked(old('mfa_required'))>
                        MFA obrigatório
                    </label>
                </div>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Função na equipa</span>
                    <input name="role_in_team" value="{{ old('role_in_team') }}" class="mv-input">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Observações internas</span>
                    <textarea name="internal_notes" rows="3" class="mv-input">{{ old('internal_notes') }}</textarea>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Justificação</span>
                    <textarea name="justification" rows="3" class="mv-input" required>{{ old('justification') }}</textarea>
                </label>
                <div class="flex gap-3">
                    <button class="mv-button-primary">Criar</button>
                    <a href="{{ route('backoffice.users.index') }}" class="mv-button-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
