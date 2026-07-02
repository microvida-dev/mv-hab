@props(['events' => []])

@php
    $hours = range(8, 18);
    $eventsCollection = collect($events);

    $eventsByHour = $eventsCollection
        ->sortBy(fn ($event) => $event['datetime'] ?? '9999-12-31T23:59:59')
        ->groupBy(fn ($event) => substr((string) ($event['time'] ?? 'Sem hora'), 0, 2));
@endphp

<div>
    @if ($eventsCollection->isEmpty())
        <x-agenda.empty-state />
    @else
        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="grid grid-cols-[88px_minmax(0,1fr)] bg-slate-50 px-4 py-3">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                    Hora
                </div>
                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                    Eventos operacionais
                </div>
            </div>

            <div class="relative">
                @foreach ($hours as $hour)
                    @php
                        $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
                        $hourEvents = $eventsByHour->get($key, collect());
                    @endphp

                    <div class="grid grid-cols-[88px_minmax(0,1fr)] border-t border-slate-100">
                        <div class="bg-slate-50 px-4 py-6 text-right">
                            <p class="text-sm font-bold text-slate-700">{{ $key }}:00</p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ $key }}:30</p>
                        </div>

                        <div class="relative min-h-[108px] border-l border-slate-100 px-4 py-3">
                            <div class="absolute left-0 right-0 top-1/2 border-t border-dashed border-slate-200"></div>

                            @if ($hourEvents->isEmpty())
                                <div class="relative h-full rounded-2xl border border-dashed border-slate-100 bg-slate-50/40"></div>
                            @else
                                <div class="relative z-10 space-y-3">
                                    @foreach ($hourEvents as $event)
                                        <x-agenda.event-card :event="$event" />
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
