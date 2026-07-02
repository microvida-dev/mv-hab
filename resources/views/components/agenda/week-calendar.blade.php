@props(['days' => []])

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

                        $eventClasses = 'block rounded-2xl border border-slate-200 bg-white p-3 text-left shadow-sm transition';

                        if ($eventHref) {
                            $eventClasses .= ' hover:border-blue-500 hover:bg-blue-50 hover:shadow-lg';
                        }
                    @endphp

                    @if ($eventHref)
                        <a href="{{ $eventHref }}" class="{{ $eventClasses }}">
                            <p class="text-xs font-bold text-slate-400">{{ $event['time'] ?? '—' }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $event['title'] ?? 'Evento' }}</p>
                        </a>
                    @else
                        <div class="{{ $eventClasses }}">
                            <p class="text-xs font-bold text-slate-400">{{ $event['time'] ?? '—' }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $event['title'] ?? 'Evento' }}</p>
                        </div>
                    @endif
                @empty
                    <p class="text-xs text-slate-400">Sem eventos.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
