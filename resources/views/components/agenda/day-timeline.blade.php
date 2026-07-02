@props(['events' => []])

@php
    $workspaceStyles = [
        'operations' => 'border-blue-200 bg-blue-50/70 text-blue-800',
        'applications' => 'border-indigo-200 bg-indigo-50/70 text-indigo-800',
        'contests' => 'border-purple-200 bg-purple-50/70 text-purple-800',
        'patrimony' => 'border-emerald-200 bg-emerald-50/70 text-emerald-800',
        'maintenance' => 'border-orange-200 bg-orange-50/70 text-orange-800',
        'tenant' => 'border-cyan-200 bg-cyan-50/70 text-cyan-800',
        'finance' => 'border-amber-200 bg-amber-50/70 text-amber-800',
        'administration' => 'border-slate-200 bg-slate-50 text-slate-800',
    ];

    $priorityStyles = [
        'critical' => 'bg-red-100 text-red-700',
        'high' => 'bg-orange-100 text-orange-700',
        'medium' => 'bg-blue-100 text-blue-700',
        'low' => 'bg-slate-100 text-slate-600',
    ];

    $eventsByHour = collect($events)->groupBy(fn ($event) => substr((string) ($event['time'] ?? 'Sem hora'), 0, 2));
    $hours = range(8, 18);
@endphp

<div class="space-y-4">
    @if (empty($events))
        <div class="rounded-[2rem] border border-dashed border-slate-300 bg-slate-50 p-12 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-2xl shadow-sm">
                📅
            </div>
            <p class="mt-5 text-lg font-bold text-slate-800">Sem eventos agendados</p>
            <p class="mt-1 text-sm text-slate-500">
                Não existem eventos para o período selecionado. Experimente alterar a data ou remover filtros.
            </p>
        </div>
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
                                    @php
                                        $workspace = $event['workspace'] ?? 'administration';
                                        $priority = $event['priority'] ?? 'medium';
                                        $cardStyle = $workspaceStyles[$workspace] ?? $workspaceStyles['administration'];
                                        $priorityStyle = $priorityStyles[$priority] ?? $priorityStyles['medium'];
                                    @endphp

                                    <article class="rounded-3xl border p-4 shadow-sm transition hover:shadow-md {{ $cardStyle }}">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide">
                                                        {{ $event['time'] ?? '—' }}
                                                    </span>

                                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $priorityStyle }}">
                                                        {{ $priority }}
                                                    </span>

                                                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide">
                                                        {{ $workspace }}
                                                    </span>

                                                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide">
                                                        {{ $event['status'] ?? 'pending' }}
                                                    </span>
                                                </div>

                                                <h3 class="mt-3 text-base font-bold text-slate-950">
                                                    {{ $event['title'] }}
                                                </h3>

                                                @if (! empty($event['description']))
                                                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-700">
                                                        {{ $event['description'] }}
                                                    </p>
                                                @endif

                                                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                                    <span>{{ $event['type'] ?? 'evento' }}</span>
                                                    @if (! empty($event['metadata']['task_number']))
                                                        <span>·</span>
                                                        <span>{{ $event['metadata']['task_number'] }}</span>
                                                    @endif
                                                    @if (! empty($event['metadata']['visit_number']))
                                                        <span>·</span>
                                                        <span>{{ $event['metadata']['visit_number'] }}</span>
                                                    @endif
                                                    @if (! empty($event['metadata']['inspection_number']))
                                                        <span>·</span>
                                                        <span>{{ $event['metadata']['inspection_number'] }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            @if (! empty($event['route']))
                                                <a href="{{ route($event['route']) }}" class="shrink-0 text-sm font-bold text-blue-700 hover:underline">
                                                    Abrir →
                                                </a>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
