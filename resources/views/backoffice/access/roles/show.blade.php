<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Gestão de acessos"
            :title="$role->label"
            :description="'Identificador técnico: '.$role->name"
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.roles.index') }}" class="mv-button-secondary">Voltar</a>
                @can('update', $role)
                    <a href="{{ route('backoffice.roles.edit', $role) }}" class="mv-button-primary">Editar</a>
                @endcan
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @error('role')
                <x-mv.alert tone="danger">{{ $message }}</x-mv.alert>
            @enderror

            @if ($role->is_system)
                <x-mv.alert tone="info">
                    Este perfil é estrutural e encontra-se em modo de leitura. Pode ser duplicado para criar uma variante municipal independente.
                </x-mv.alert>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <x-mv.section title="Resumo" padding="p-5" class="lg:col-span-2">
                    <dl class="grid gap-4 text-sm md:grid-cols-2">
                        <div><dt class="text-ink-500">Designação</dt><dd class="mt-1 font-medium text-ink-900">{{ $role->label }}</dd></div>
                        <div><dt class="text-ink-500">Identificador</dt><dd class="mt-1 font-medium text-ink-900">{{ $role->name }}</dd></div>
                        <div><dt class="text-ink-500">Tipo</dt><dd class="mt-1"><x-mv.badge>{{ $role->is_system ? 'Sistema' : 'Municipal personalizada' }}</x-mv.badge></dd></div>
                        <div><dt class="text-ink-500">Estado</dt><dd class="mt-1"><x-mv.badge :tone="$role->is_active ? 'success' : 'warning'">{{ $role->is_active ? 'Ativa' : 'Inativa' }}</x-mv.badge></dd></div>
                        <div class="md:col-span-2"><dt class="text-ink-500">Descrição</dt><dd class="mt-1 text-ink-900">{{ $role->description ?: 'Sem descrição.' }}</dd></div>
                    </dl>
                </x-mv.section>

                <x-mv.section title="Utilização" padding="p-5">
                    <p class="text-3xl font-semibold text-ink-900">{{ $role->users_count }}</p>
                    <p class="mt-1 text-sm text-ink-500">utilizadores associados</p>
                    <a href="{{ route('backoffice.roles.users', $role) }}" class="mv-button-secondary mt-4">Consultar utilizadores</a>
                </x-mv.section>
            </div>

            <x-mv.section title="Permissões efetivas" padding="p-5">
                <div class="flex flex-wrap gap-2">
                    @forelse ($role->permissions->sortBy('name') as $permission)
                        <x-mv.badge>{{ $permission->name }}</x-mv.badge>
                    @empty
                        <p class="text-sm text-ink-500">Este perfil não tem permissões configuradas.</p>
                    @endforelse
                </div>
            </x-mv.section>

            <div class="grid gap-6 xl:grid-cols-2">
                @can('duplicate', $role)
                    <x-mv.section title="Duplicar perfil" description="Cria um perfil municipal sem copiar utilizadores." padding="p-5">
                        <form method="POST" action="{{ route('backoffice.roles.duplicate', $role) }}" class="grid gap-3">
                            @csrf
                            <input name="label" class="mv-input" value="{{ old('label', $role->label.' - cópia') }}" required>
                            <textarea name="description" rows="2" class="mv-input">{{ old('description', $role->description) }}</textarea>
                            <textarea name="justification" rows="2" class="mv-input" placeholder="Justificação administrativa" required>{{ old('justification') }}</textarea>
                            <div><button type="submit" class="mv-button-secondary">Duplicar</button></div>
                        </form>
                    </x-mv.section>
                @endcan

                @can('toggle', $role)
                    <x-mv.section title="Estado do perfil" padding="p-5">
                        <form method="POST" action="{{ $role->is_active ? route('backoffice.roles.deactivate', $role) : route('backoffice.roles.activate', $role) }}" class="grid gap-3">
                            @csrf
                            <textarea name="justification" rows="2" class="mv-input" placeholder="Justificação administrativa" required></textarea>
                            <div>
                                <button type="submit" class="{{ $role->is_active ? 'mv-button-danger' : 'mv-button-primary' }}">
                                    {{ $role->is_active ? 'Desativar perfil' : 'Ativar perfil' }}
                                </button>
                            </div>
                        </form>
                    </x-mv.section>
                @endcan
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('audit', $role)
                    <a href="{{ route('backoffice.roles.audit', $role) }}" class="mv-button-secondary">Ver auditoria</a>
                @endcan
            </div>

            @can('delete', $role)
                <x-mv.section title="Eliminar perfil" description="Só é possível eliminar perfis sem utilizadores associados." padding="p-5">
                    <form method="POST" action="{{ route('backoffice.roles.destroy', $role) }}" class="grid gap-3 md:grid-cols-[1fr_auto]">
                        @csrf
                        @method('DELETE')
                        <input name="justification" class="mv-input" placeholder="Justificação administrativa" required>
                        <button type="submit" class="mv-button-danger">Eliminar perfil</button>
                    </form>
                </x-mv.section>
            @endcan

        </div>
    </div>
</x-app-layout>
