@props([
    'items',
])

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div>
        <h3 class="font-semibold text-ink-900">Carga operacional</h3>
        <p class="mt-1 text-sm text-ink-500">Distribuição agregada por responsável e equipa, sem dados de candidatos.</p>
    </div>

    @if (($items ?? []) === [])
        <x-analytics.analytics-empty-state title="Sem carga operacional" />
    @else
        <div class="overflow-x-auto">
            <table class="mv-table text-sm">
                <caption class="sr-only">Carga operacional por responsável e equipa</caption>
                <thead>
                    <tr>
                        <th>Responsável</th>
                        <th>Equipa</th>
                        <th>Total</th>
                        <th>Em atraso</th>
                        <th>A vencer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item['name'] ?? 'Sem responsável' }}</td>
                            <td>{{ $item['team'] ?? 'Sem equipa' }}</td>
                            <td>{{ $item['total'] ?? 0 }}</td>
                            <td>{{ $item['overdue'] ?? 0 }}</td>
                            <td>{{ $item['due_soon'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
