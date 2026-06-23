<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Relatórios de custos</h1></x-slot>
    <div class="grid gap-4 md:grid-cols-3">@foreach ($summary as $title => $rows)<div class="mv-card"><h2 class="font-semibold">{{ str($title)->replace('_', ' ')->title() }}</h2>@foreach ($rows as $row)<p class="mt-2 text-sm">{{ $row->housingUnit?->code ?? $row->supplier?->name ?? $row->name ?? 'Sem detalhe' }} · {{ number_format((float) $row->total, 2, ',', '.') }} EUR</p>@endforeach</div>@endforeach</div>
</x-app-layout>
