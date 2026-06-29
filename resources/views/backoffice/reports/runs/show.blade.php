<x-app-layout>
    <x-slot name="header"><div><h1 class="text-xl font-semibold text-ink-900">{{ $run->definition->name }}</h1><p class="mt-1 text-sm text-ink-500">Execução {{ $run->public_id }}</p></div></x-slot>
    <div class="space-y-6">
        <div class="flex flex-wrap gap-5 text-sm text-ink-600"><span>Estado: <strong>{{ $run->status->label() }}</strong></span><span>Linhas: <strong>{{ $run->row_count }}</strong></span><span>Âmbito: <strong>{{ $run->scope->label() }}</strong></span></div>
        <x-ui.table :headers="$rows !== [] ? array_keys($rows[0]) : []">
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>
                            {{ is_array($value)
                                ? json_encode($value, JSON_UNESCAPED_UNICODE)
                                : $value }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <x-ui.table-empty
                    :colspan="max(1, count($rows !== [] ? array_keys($rows[0]) : []))"
                    message="Sem resultados para os filtros aplicados."
                />
            @endforelse
        </x-ui.table>
    </div>
</x-app-layout>
