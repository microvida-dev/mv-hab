<x-app-layout>
    @php($summary = $agenda['statistics'] ?? $agenda['summary'] ?? [])

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-agenda.toolbar :filters="$filters" :options="$options" />

        <x-agenda.summary-ribbon :summary="$summary" :agenda="$agenda" :filters="$filters" />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
            <section class="rounded-[2rem] border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">
                            Vista {{ match($filters['view'] ?? 'day') { 'day' => 'diária', 'week' => 'semanal', 'month' => 'mensal', default => 'agenda' } }}
                        </p>
                        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">
                            {{ $agenda['label'] ?? 'Agenda' }}
                        </h2>
                    </div>

                    <span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700">
                        {{ $summary['total'] ?? 0 }} eventos
                    </span>
                </div>

                @if (($filters['view'] ?? 'day') === 'day')
                    <x-agenda.day-timeline :events="$agenda['events'] ?? []" />
                @elseif (($filters['view'] ?? 'day') === 'week')
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-7">
                        @foreach ($agenda['days'] ?? [] as $day)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase text-slate-400">{{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('D') }}</p>
                                <h3 class="mt-1 text-sm font-bold text-slate-950">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m') }}</h3>
                                <p class="mt-1 text-xs font-semibold text-blue-700">{{ $day['statistics']['total'] ?? 0 }} eventos</p>

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
                @else
                    <div class="space-y-4">
                        @foreach ($agenda['weeks'] ?? [] as $week)
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
                                            <p class="text-sm font-bold text-slate-900">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d') }}</p>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $day['statistics']['total'] ?? 0 }} eventos</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="space-y-4">
                <div class="rounded-[2rem] border border-slate-200/80 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Resumo</p>

                    <div class="mt-5 grid gap-3">
                        @foreach ([
                            'Total' => $summary['total'] ?? 0,
                            'Críticos' => $summary['critical'] ?? 0,
                            'Alta prioridade' => $summary['high'] ?? 0,
                            'Em atraso' => $summary['overdue'] ?? 0,
                        ] as $label => $value)
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                                <p class="mt-1 text-2xl font-bold text-slate-950">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
