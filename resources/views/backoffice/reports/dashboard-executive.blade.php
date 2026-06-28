<x-app-layout>
    <x-slot name="header"><div><h1 class="text-xl font-semibold text-ink-900">Painel executivo</h1><p class="mt-1 text-sm text-ink-500">Visão agregada para decisão municipal.</p></div></x-slot>
    <div class="space-y-8">
        @include('backoffice.reports._filters')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($widgets as $item)
                <div class="mv-card min-h-32"><p class="text-sm text-ink-600">{{ $item['widget']->title }}</p><p class="mt-3 text-3xl font-semibold text-ink-900">{{ is_array($item['result']['value']) ? array_sum($item['result']['value']) : number_format((float) ($item['result']['value'] ?? 0), 2, ',', '.') }}</p></div>
            @endforeach
        </div>
        <section><h2 class="text-lg font-semibold text-ink-900">Candidaturas por estado</h2><div class="mt-3 overflow-x-auto"><table class="mv-table"><thead><tr><th>Estado</th><th>Total</th></tr></thead><tbody>@foreach ($by_status as $status => $total)<tr><td>{{ \App\Enums\ApplicationStatus::tryFrom($status)?->label() ?? $status }}</td><td>{{ $total }}</td></tr>@endforeach</tbody></table></div></section>
        <section><h2 class="text-lg font-semibold text-ink-900">Resumo por concurso</h2><div class="mt-3 overflow-x-auto"><table class="mv-table"><thead><tr><th>Programa</th><th>Concurso</th><th>Estado</th><th>Total</th></tr></thead><tbody>@foreach ($by_contest as $row)<tr>@foreach ($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody></table></div></section>
    </div>
</x-app-layout>
