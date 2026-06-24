<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Editar utilizador</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @error('access')
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('backoffice.users.update', $user) }}" class="mv-surface grid gap-4 p-6">
                @csrf
                @method('PATCH')
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Nome</span>
                    <input name="name" value="{{ old('name', $user->name) }}" class="rounded-md border-ink-200" required>
                </label>
                <div class="rounded-md bg-ink-50 p-4 text-sm text-ink-600">
                    Email: {{ $user->email }}. Alteração de email exige política própria e não é feita neste fluxo.
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-ink-700">
                    <input type="checkbox" name="mfa_required" value="1" @checked(old('mfa_required', $user->mfa_required))>
                    MFA obrigatório
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Observações internas</span>
                    <textarea name="internal_notes" rows="3" class="rounded-md border-ink-200">{{ old('internal_notes', $user->internal_notes) }}</textarea>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium text-ink-700">Justificação</span>
                    <textarea name="justification" rows="3" class="rounded-md border-ink-200" required>{{ old('justification') }}</textarea>
                </label>
                <div class="flex gap-3">
                    <button class="mv-button-primary">Guardar</button>
                    <a href="{{ route('backoffice.users.show', $user) }}" class="mv-button-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
