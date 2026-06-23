<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $propertyInspection->inspection_number }}</h1></x-slot>
    <div class="space-y-6"><div class="mv-card"><p>{{ $propertyInspection->inspection_type->label() }} · {{ $propertyInspection->status->label() }}</p><p class="mt-2">{{ $propertyInspection->summary }}</p></div>@if($propertyInspection->report)<a class="mv-button-secondary" href="{{ route('candidate.inspections.reports.download', $propertyInspection->report) }}">Descarregar auto</a>@endif<div class="mv-card">@foreach ($propertyInspection->items as $item)<p class="py-2 text-sm">{{ $item->label }} · {{ $item->condition?->label() ?? '-' }}</p>@endforeach</div></div>
</x-app-layout>
