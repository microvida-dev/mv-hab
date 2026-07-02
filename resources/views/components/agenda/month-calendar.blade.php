@props(['weeks' => []])

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
                                    @endphp

                                    <a href="{{ $eventHref }}"
                                        class="block truncate rounded-lg bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                        @if (! empty($event['time']))
                                            <span class="text-slate-400">{{ $event['time'] }}</span>
                                        @endif

                                        {{ $event['title'] ?? 'Evento' }}
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
