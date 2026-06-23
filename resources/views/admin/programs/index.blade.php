<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Configuração pública</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Programas</h1>
            </div>
            @can('create', \App\Models\Program::class)
                <a href="{{ route('admin.programs.create') }}" class="mv-button-primary">
                    <x-ui-icon name="plus" class="h-4 w-4" />
                    Novo programa
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table">
                        <thead>
                            <tr>
                                <th>Programa</th>
                                <th>Município</th>
                                <th>Estado</th>
                                <th>Concursos</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($programs as $program)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">{{ $program->name }}</p>
                                        <p class="mt-1 text-xs text-ink-500">{{ $program->slug }}</p>
                                    </td>
                                    <td>{{ $program->municipality->name }}</td>
                                    <td>{{ $program->status->label() }}</td>
                                    <td>{{ $program->contests_count }}</td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.programs.show', $program) }}" class="mv-button-secondary min-h-0 px-3 py-1.5 text-xs">Ver</a>
                                            @can('update', $program)
                                                <a href="{{ route('admin.programs.edit', $program) }}" class="mv-button-secondary min-h-0 px-3 py-1.5 text-xs">Editar</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-ink-500">Ainda não existem programas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 px-4 py-4">{{ $programs->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
