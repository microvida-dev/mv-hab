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
                @forelse ($day['events'] ?? [] as $event)
                    <div class="rounded-2xl bg-white p-3 shadow-sm">
                        <p class="text-xs font-bold text-slate-400">{{ $event['time'] ?? '—' }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">{{ $event['title'] }}</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Sem eventos.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
