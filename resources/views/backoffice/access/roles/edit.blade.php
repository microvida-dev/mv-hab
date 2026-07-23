<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Perfil municipal"
            :title="$role->label"
            :description="'Identificador técnico: '.$role->name"
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.roles.show', $role) }}" class="mv-button-secondary">Ver perfil</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @if ($errors->any())
                <x-mv.alert tone="danger">Corrija os campos assinalados antes de guardar.</x-mv.alert>
            @endif

            <form method="POST" action="{{ route('backoffice.roles.update', $role) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('backoffice.access.roles.partials.details', ['role' => $role])
                <x-mv.section title="Justificação da alteração" padding="p-5">
                    <textarea name="justification" rows="3" class="mv-input" required>{{ old('justification') }}</textarea>
                    <x-input-error :messages="$errors->get('justification')" />
                </x-mv.section>
                <div class="flex justify-end">
                    <button type="submit" class="mv-button-primary">Guardar dados</button>
                </div>
            </form>

            <form method="POST" action="{{ route('backoffice.roles.permissions.update', $role) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('backoffice.access.roles.partials.permissions', [
                    'selectedPermissionIds' => old('permissions', $role->permissions->modelKeys()),
                    'readOnly' => false,
                ])
                <x-mv.section title="Justificação da matriz" padding="p-5">
                    <textarea name="justification" rows="3" class="mv-input" required>{{ old('justification') }}</textarea>
                </x-mv.section>
                <div class="flex justify-end">
                    <button type="submit" class="mv-button-primary">Guardar permissões</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
