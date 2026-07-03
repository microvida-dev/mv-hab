@props(['weeks' => []])

@once
    <style>
        .agenda-month-event-card {
            transition:
                background-color .2s ease,
                border-color .2s ease,
                color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }

        .agenda-month-event-card:hover {
            background: #2563EB !important;
            border-color: #2563EB !important;
            color: #fff !important;
            box-shadow: 0 12px 24px rgba(37,99,235,.28);
            transform: translateY(-2px);
        }

        .agenda-month-event-card:hover .agenda-month-event-time,
        .agenda-month-event-card:hover .agenda-month-event-title {
            color: #fff !important;
        }

        .agenda-month-event-card:hover .agenda-month-event-time {
            opacity: .9;
        }
    </style>
@endonce

<div class="space-y-4">
    @foreach ($weeks as $week)
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-950">{{ $week['label'] }}</h3>

                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-700 shadow-sm">
                    {{ $week['summary']['total'] ?? 0 }} eventos
                </span>
            </div>

            <div class="grid gap-3 md:grid-cols-7">
                @foreach ($week['days'] ?? [] as $day)
                    <div class="min-h-32 rounded-2xl bg-white p-3 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-bold text-slate-900">
                                {{ \Illuminate\Support\Carbon::parse($day['date'])->format('d') }}
                            </p>

                            <span class="text-[11px] font-bold text-slate-400">
                                {{ $day['statistics']['total'] ?? 0 }}
                            </span>
                        </div>

                        @if (! empty($day['events']))
                            <div class="mt-3 space-y-1">
                                @foreach (collect($day['events'])->take(3) as $event)
                                    @php
                                        $eventRoute = $event['route'] ?? null;
                                        $eventHref = null;

                                        if ($eventRoute) {
                                            $eventHref = \Illuminate\Support\Str::startsWith($eventRoute, ['http://', 'https://', '/'])
                                                ? $eventRoute
                                                : route($eventRoute);
                                        }

                                        $eventClasses = 'agenda-month-event-card block truncate rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700 shadow-sm';

                                        if (! $eventHref) {
                                            $eventClasses .= ' opacity-80';
                                        }
                                    @endphp

                                    <a href="{{ $eventHref ?: '#' }}" class="{{ $eventClasses }}">
                                        @if (! empty($event['time']))
                                            <span class="agenda-month-event-time text-slate-400">
                                                {{ $event['time'] }}
                                            </span>
                                        @endif

                                        <span class="agenda-month-event-title text-slate-700">
                                            {{ $event['title'] ?? 'Evento' }}
                                        </span>
                                    </a>
                                @endforeach

                                @if (count($day['events']) > 3)
                                    <p class="px-2 text-[11px] font-bold text-slate-400">
                                        +{{ count($day['events']) - 3 }} eventos
                                    </p>
                                @endif
                            </div>
                        @else
                            <p class="mt-3 text-xs font-semibold text-slate-400">
                                Sem eventos
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
