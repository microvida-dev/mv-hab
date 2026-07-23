<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Utilizadores associados"
            :title="$role->label"
            :description="$role->users_count.' utilizador(es) associado(s)'"
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.roles.show', $role) }}" class="mv-button-secondary">Voltar ao perfil</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <thead><tr><th>Utilizador</th><th>Estado</th><th>Outros perfis</th><th class="text-right">Ação</th></tr></thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="font-medium text-ink-900">{{ $user->name }}</td>
                                    <td><x-mv.badge :tone="$user->status === 'active' ? 'success' : 'warning'">{{ $user->status === 'active' ? 'Ativo' : 'Inativo' }}</x-mv.badge></td>
                                    <td>{{ $user->roles->where('id', '!=', $role->id)->pluck('label')->join(', ') ?: 'Sem outros perfis' }}</td>
                                    <td class="text-right"><a href="{{ route('backoffice.users.show', $user) }}" class="mv-button-secondary">Abrir utilizador</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Não existem utilizadores associados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $users->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
