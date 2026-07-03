@props(['days' => []])

@once
    <style>
        .agenda-event-card {
            transition:
                background-color .2s ease,
                border-color .2s ease,
                color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }

        .agenda-event-card:hover {
            background: #2563EB !important;
            border-color: #2563EB !important;
            color: #fff !important;
            box-shadow: 0 12px 24px rgba(37,99,235,.28);
            transform: translateY(-2px);
        }

        .agenda-event-card:hover .agenda-event-time,
        .agenda-event-card:hover .agenda-event-title {
            color: #fff !important;
        }

        .agenda-event-card:hover .agenda-event-time {
            opacity: .9;
        }
    </style>
@endonce

<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-7">
    @foreach ($days as $day)
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-bold uppercase text-slate-400">
                {{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('D') }}
            </p>

            <h3 class="mt-1 text-sm font-bold text-slate-950">
                {{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m') }}
            </h3>

            <p class="mt-1 text-xs font-semibold text-blue-700">
                {{ $day['statistics']['total'] ?? 0 }} eventos
            </p>

            <div class="mt-4 space-y-2">
                @forelse (collect($day['events'] ?? [])->sortBy('datetime')->values() as $event)
                    @php
                        $eventRoute = $event['route'] ?? null;
                        $eventHref = null;

                        if ($eventRoute) {
                            $eventHref = \Illuminate\Support\Str::startsWith($eventRoute, ['http://', 'https://', '/'])
                                ? $eventRoute
                                : route($eventRoute);
                        }

                        $eventClasses = 'agenda-event-card block rounded-2xl border border-slate-200 bg-white p-3 text-left shadow-sm';

                        if (! $eventHref) {
                            $eventClasses .= ' opacity-80';
                        }
                    @endphp

                    @if ($eventHref)
                        <a href="{{ $eventHref }}" class="{{ $eventClasses }}">
                            <p class="agenda-event-time text-xs font-bold text-slate-400">
                                {{ $event['time'] ?? '—' }}
                            </p>

                            <p class="agenda-event-title mt-1 text-sm font-bold text-slate-900">
                                {{ $event['title'] ?? 'Evento' }}
                            </p>
                        </a>
                    @else
                        <div class="{{ $eventClasses }}">
                            <p class="agenda-event-time text-xs font-bold text-slate-400">
                                {{ $event['time'] ?? '—' }}
                            </p>

                            <p class="agenda-event-title mt-1 text-sm font-bold text-slate-900">
                                {{ $event['title'] ?? 'Evento' }}
                            </p>
                        </div>
                    @endif
                @empty
                    <p class="text-xs text-slate-400">Sem eventos.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
