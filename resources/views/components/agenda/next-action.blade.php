@props(['event' => null])

@if ($event)
    @php
        $priorityStyles = [
            'critical' => 'bg-red-100 text-red-700',
            'high' => 'bg-orange-100 text-orange-700',
            'medium' => 'bg-blue-100 text-blue-700',
            'low' => 'bg-slate-100 text-slate-600',
        ];

        $priority = $event['priority'] ?? 'medium';
        $priorityStyle = $priorityStyles[$priority] ?? $priorityStyles['medium'];
    @endphp

    <section class="rounded-[2rem] border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-slate-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-700">
                    Próxima ação recomendada
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $priorityStyle }}">
                        {{ $priority }}
                    </span>

                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600 shadow-sm">
                        {{ $event['workspace'] ?? 'workspace' }}
                    </span>

                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600 shadow-sm">
                        {{ $event['status'] ?? 'pending' }}
                    </span>

                    @if (! empty($event['time']))
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600 shadow-sm">
                            {{ $event['time'] }}
                        </span>
                    @endif
                </div>

                <h2 class="mt-3 text-xl font-black tracking-tight text-slate-950">
                    {{ $event['title'] }}
                </h2>

                @if (! empty($event['description']))
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                        {{ $event['description'] }}
                    </p>
                @endif

                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                    <span>{{ $event['type'] ?? 'evento' }}</span>

                    @foreach (['task_number', 'visit_number', 'inspection_number', 'complaint_number', 'hearing_number', 'request_number', 'decision_number'] as $key)
                        @if (! empty($event['metadata'][$key]))
                            <span>·</span>
                            <span>{{ $event['metadata'][$key] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>

            @if (! empty($event['route']))
                <a href="{{ route($event['route']) }}" class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-blue-700">
                    Abrir processo →
                </a>
            @endif
        </div>
    </section>
@endif
