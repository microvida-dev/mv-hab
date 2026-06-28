<x-app-layout>
    <x-slot name="header"><div><h1 class="text-xl font-semibold text-ink-900">Painel operacional</h1><p class="mt-1 text-sm text-ink-500">Carga de trabalho e alertas correntes.</p></div></x-slot>
    <div class="space-y-6">
        @include('backoffice.reports._filters')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($widgets as $item)
                <div class="mv-card min-h-32">
                    <p class="text-sm font-medium text-ink-600">{{ $item['widget']->title }}</p>
                    @if ($item['result']['status'] === 'available')
                        <p class="mt-3 text-3xl font-semibold text-ink-900">
                            @if (is_array($item['result']['value'])){{ array_sum($item['result']['value']) }}@else{{ is_numeric($item['result']['value']) ? number_format((float) $item['result']['value'], 2, ',', '.') : $item['result']['value'] }}@endif
                        </p>
                    @else
                        <p class="mt-3 text-sm font-medium text-ink-500">Indisponível</p>
                    @endif
                    <p class="mt-3 text-xs text-ink-400">Atualizado {{ $item['result']['calculated_at']->format('H:i') }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
