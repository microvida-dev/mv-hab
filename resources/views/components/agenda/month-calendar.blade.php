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
                    <div class="rounded-2xl bg-white p-3 shadow-sm">
                        <p class="text-sm font-bold text-slate-900">
                            {{ \Illuminate\Support\Carbon::parse($day['date'])->format('d') }}
                        </p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            {{ $day['statistics']['total'] ?? 0 }} eventos
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
