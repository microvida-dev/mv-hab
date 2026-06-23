<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Configuração pública</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Concursos</h1>
            </div>
            @can('create', \App\Models\Contest::class)
                <a href="{{ route('admin.contests.create') }}" class="mv-button-primary">
                    <x-ui-icon name="plus" class="h-4 w-4" />
                    Novo concurso
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
                                <th>Concurso</th>
                                <th>Programa</th>
                                <th>Estado</th>
                                <th>Prazo</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($contests as $contest)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">{{ $contest->title }}</p>
                                        <p class="mt-1 text-xs text-ink-500">{{ $contest->code }}</p>
                                    </td>
                                    <td>{{ $contest->program->name }}</td>
                                    <td>{{ $contest->status->label() }}</td>
                                    <td>{{ $contest->opens_at->format('d/m/Y') }} — {{ $contest->closes_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.contests.show', $contest) }}" class="mv-button-secondary min-h-0 px-3 py-1.5 text-xs">Ver</a>
                                            @can('update', $contest)
                                                <a href="{{ route('admin.contests.edit', $contest) }}" class="mv-button-secondary min-h-0 px-3 py-1.5 text-xs">Editar</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-ink-500">Ainda não existem concursos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 px-4 py-4">{{ $contests->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
