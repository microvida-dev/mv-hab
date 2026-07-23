<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Gestão de acessos"
            title="Criar perfil municipal"
            description="O identificador técnico será criado automaticamente e ficará estável."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.roles.index') }}" class="mv-button-secondary">Voltar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <x-mv.alert tone="danger">Corrija os campos assinalados antes de guardar.</x-mv.alert>
            @endif

            <form method="POST" action="{{ route('backoffice.roles.store') }}" class="space-y-6">
                @csrf
                @include('backoffice.access.roles.partials.details', ['role' => null])
                @include('backoffice.access.roles.partials.permissions', [
                    'selectedPermissionIds' => old('permissions', []),
                    'readOnly' => false,
                ])

                <x-mv.section title="Justificação administrativa" padding="p-5">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Justificação</span>
                        <textarea name="justification" rows="3" class="mv-input" required>{{ old('justification') }}</textarea>
                        <x-input-error :messages="$errors->get('justification')" />
                    </label>
                </x-mv.section>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('backoffice.roles.index') }}" class="mv-button-secondary">Cancelar</a>
                    <button type="submit" class="mv-button-primary">Criar perfil</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
