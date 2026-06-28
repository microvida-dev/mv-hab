@props([
    'rows',
    'caption' => 'Tabela analítica',
    'compact' => false,
])

@php
    $rows = array_values($rows ?? []);
    $columns = $rows === [] ? [] : array_keys($rows[0]);
@endphp

@if ($rows === [])
    <x-analytics.analytics-empty-state title="Sem linhas analíticas" />
@else
    <div {{ $attributes->merge(['class' => 'overflow-x-auto '.($compact ? 'mt-4' : '')]) }}>
        <table class="mv-table text-sm">
            <caption class="{{ $compact ? 'sr-only' : 'mb-2 text-left text-sm font-medium text-ink-600' }}">{{ $caption }}</caption>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ str($column)->replace('_', ' ')->title() }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($columns as $column)
                            <td>{{ is_scalar($row[$column] ?? null) ? $row[$column] : '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
