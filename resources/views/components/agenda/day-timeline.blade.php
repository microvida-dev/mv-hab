@props(['events' => []])

@php
    $eventsByHour = collect($events)->groupBy(fn ($event) => substr((string) ($event['time'] ?? 'Sem hora'), 0, 2));
    $hours = range(8, 18);
@endphp

<div class="space-y-4">
    @if (empty($events))
        <x-agenda.empty-state />
    @else
        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-50">
            @foreach ($hours as $hour)
                @php
                    $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
                    $hourEvents = $eventsByHour->get($key, collect());
                @endphp

                <div class="grid grid-cols-[86px_minmax(0,1fr)] border-b border-slate-200 last:border-b-0">
                    <div class="border-r border-slate-200 bg-white px-4 py-5 text-right">
                        <p class="text-sm font-bold text-slate-700">{{ $key }}:00</p>
                    </div>

                    <div class="min-h-[88px] px-4 py-3">
                        @if ($hourEvents->isEmpty())
                            <div class="h-full rounded-2xl border border-dashed border-slate-200 bg-white/60"></div>
                        @else
                            <div class="space-y-3">
                                @foreach ($hourEvents as $event)
                                    <x-agenda.event-card :event="$event" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
