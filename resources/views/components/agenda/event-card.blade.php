@props(['event'])

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

    $workspace = $event['workspace'] ?? 'administration';
    $priority = $event['priority'] ?? 'medium';
    $metadata = $event['metadata'] ?? [];

    $cardStyle = $workspaceStyles[$workspace] ?? $workspaceStyles['administration'];
    $priorityStyle = $priorityStyles[$priority] ?? $priorityStyles['medium'];

    $references = [
        'Tarefa' => $metadata['task_number'] ?? null,
        'Visita' => $metadata['visit_number'] ?? null,
        'Vistoria' => $metadata['inspection_number'] ?? null,
        'Reclamação' => $metadata['complaint_number'] ?? null,
        'Audiência' => $metadata['hearing_number'] ?? null,
        'Pedido' => $metadata['request_number'] ?? null,
        'Decisão' => $metadata['decision_number'] ?? null,
        'Processo' => $metadata['process_number'] ?? null,
        'Candidatura' => $metadata['application_number'] ?? null,
        'Concurso' => $metadata['contest_number'] ?? null,
    ];

    $context = [
        'Técnico' => $metadata['assigned_to_name'] ?? $metadata['technician_name'] ?? null,
        'Imóvel' => $metadata['housing_unit'] ?? $metadata['property'] ?? null,
        'Local' => $metadata['location'] ?? $metadata['address'] ?? null,
        'Duração' => $metadata['duration'] ?? $metadata['duration_minutes'] ?? null,
    ];
@endphp

<article class="rounded-3xl border p-4 shadow-sm transition hover:shadow-md {{ $cardStyle }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
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

                @foreach ($references as $label => $value)
                    @if (! empty($value))
                        <span>·</span>
                        <span>{{ $label }}: {{ $value }}</span>
                    @endif
                @endforeach
            </div>

            @if (collect($context)->filter()->isNotEmpty())
                <dl class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($context as $label => $value)
                        @if (! empty($value))
                            <div class="rounded-2xl bg-white/70 px-3 py-2">
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                                <dd class="mt-1 truncate text-xs font-bold text-slate-700">
                                    {{ is_numeric($value) && $label === 'Duração' ? $value.' min' : $value }}
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif

            <div class="mt-4 flex flex-wrap gap-2">
                @if (! empty($event['route']))
                    <a href="{{ route($event['route']) }}" class="rounded-2xl bg-blue-600 px-4 py-2 text-xs font-black text-white transition hover:bg-blue-700">
                        Abrir
                    </a>
                @endif

                <button type="button" disabled class="rounded-2xl bg-white/70 px-4 py-2 text-xs font-black text-slate-400">
                    Concluir
                </button>

                <button type="button" disabled class="rounded-2xl bg-white/70 px-4 py-2 text-xs font-black text-slate-400">
                    Reagendar
                </button>

                <button type="button" disabled class="rounded-2xl bg-white/70 px-4 py-2 text-xs font-black text-slate-400">
                    Atribuir
                </button>
            </div>
        </div>
    </div>
</article>
