<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Gestão de acessos"
            title="Perfis e permissões"
            description="Configure perfis municipais com o menor conjunto de permissões necessário."
        >
            @can('create', App\Models\Role::class)
                <x-slot name="actions">
                    <a href="{{ route('backoffice.role-templates.index') }}" class="mv-button-secondary">Modelos recomendados</a>
                    <a href="{{ route('backoffice.roles.create') }}" class="mv-button-primary">Criar perfil</a>
                </x-slot>
            @endcan
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @error('role')
                <x-mv.alert tone="danger">{{ $message }}</x-mv.alert>
            @enderror

            <x-mv.section title="Filtrar perfis" padding="p-5">
                <form method="GET" class="grid gap-4 md:grid-cols-4">
                    <label class="grid gap-1 text-sm md:col-span-2">
                        <span class="font-medium text-ink-700">Pesquisa</span>
                        <input name="q" value="{{ request('q') }}" class="mv-input" placeholder="Designação ou identificador">
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Tipo</span>
                        <select name="type" class="mv-input">
                            <option value="">Todos</option>
                            <option value="system" @selected(request('type') === 'system')>Sistema</option>
                            <option value="municipal" @selected(request('type') === 'municipal')>Municipal personalizada</option>
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Estado</span>
                        <select name="status" class="mv-input">
                            <option value="">Todos</option>
                            <option value="active" @selected(request('status') === 'active')>Ativa</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inativa</option>
                        </select>
                    </label>
                    <div class="flex flex-wrap items-center gap-2 md:col-span-4">
                        <button type="submit" class="mv-button-primary">Aplicar filtros</button>
                        <a href="{{ route('backoffice.roles.index') }}" class="mv-button-secondary">Limpar</a>
                    </div>
                </form>
            </x-mv.section>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <thead>
                            <tr>
                                <th>Perfil</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Utilizadores</th>
                                <th>Permissões</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $role)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">{{ $role->label }}</p>
                                        <p class="text-xs text-ink-500">{{ $role->name }}</p>
                                    </td>
                                    <td>
                                        <x-mv.badge :tone="$role->is_system ? 'neutral' : 'success'">
                                            {{ $role->is_system ? 'Sistema' : 'Municipal personalizada' }}
                                        </x-mv.badge>
                                    </td>
                                    <td>
                                        <x-mv.badge :tone="$role->is_active ? 'success' : 'warning'">
                                            {{ $role->is_active ? 'Ativa' : 'Inativa' }}
                                        </x-mv.badge>
                                    </td>
                                    <td>{{ $role->users_count }}</td>
                                    <td>{{ $role->permissions_count }}</td>
                                    <td>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.roles.show', $role) }}" class="mv-button-secondary">Abrir</a>
                                            @can('update', $role)
                                                <a href="{{ route('backoffice.roles.edit', $role) }}" class="mv-button-secondary">Editar</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-ink-500">Não foram encontrados perfis.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $roles->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
