<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Histórico técnico {{ $housingUnit->code }}</h1></x-slot>
    <div class="mv-card">@foreach ($events as $event)<div class="border-t border-ink-100 py-3 first:border-t-0"><p class="font-semibold">{{ $event->title }}</p><p class="text-sm text-ink-500">{{ $event->occurred_at?->format('d/m/Y H:i') }} · {{ $event->event_type->label() }}</p><p class="text-sm">{{ $event->description }}</p></div>@endforeach{{ $events->links() }}</div>
</x-app-layout>
