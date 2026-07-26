@props([
    'widget',
])

@php
    $title = data_get($widget, 'title', 'Widget');
    $description = data_get($widget, 'description');
    $icon = data_get($widget, 'icon', 'dashboard');
    $value = data_get($widget, 'value');
    $meta = data_get($widget, 'meta');
    $tone = data_get($widget, 'tone', 'neutral');
    $priority = data_get($widget, 'priority', 'none');
    $href = data_get($widget, 'href');
    $cta = data_get($widget, 'cta', 'Abrir');
    $badges = collect(data_get($widget, 'badges', []))->filter();

    $toneClasses = [
        'neutral' => 'border-ink-100 bg-white',
        'primary' => 'border-mvhab-primary/20 bg-mvhab-primary/5',
        'info' => 'border-sky-200 bg-sky-50/70',
        'success' => 'border-emerald-200 bg-emerald-50/70',
        'warning' => 'border-amber-200 bg-amber-50/70',
        'danger' => 'border-red-200 bg-red-50/70',
    ][$tone] ?? 'border-ink-100 bg-white';

    $priorityLabel = [
        'critical' => 'Crítico',
        'high' => 'Alta',
        'medium' => 'Média',
        'low' => 'Baixa',
        'none' => null,
    ][$priority] ?? null;
@endphp

<article {{ $attributes->class([
    'rounded-3xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm',
    $toneClasses,
]) }}>
    <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/80 text-mvhab-primary ring-1 ring-inset ring-ink-100">
            <x-mv-icon :name="$icon" size="sm" />
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-ink-900">
                        {{ $title }}
                    </h3>

                    @if ($description)
                        <p class="mt-1 text-xs leading-5 text-ink-500">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                @if (! is_null($value))
                    <strong class="shrink-0 text-2xl font-bold text-ink-900">
                        {{ $value }}
                    </strong>
                @endif
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                @if ($meta)
                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-semibold text-ink-600 ring-1 ring-inset ring-ink-100">
                        {{ $meta }}
                    </span>
                @endif

                @if ($priorityLabel)
                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-semibold text-ink-600 ring-1 ring-inset ring-ink-100">
                        Prioridade {{ $priorityLabel }}
                    </span>
                @endif

                @foreach ($badges as $badge)
                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-semibold text-ink-600 ring-1 ring-inset ring-ink-100">
                        {{ data_get($badge, 'label') }}
                    </span>
                @endforeach
            </div>

            @if ($href)
                <div class="mt-4">
                    <a href="{{ $href }}" class="inline-flex items-center gap-2 text-xs font-bold text-mvhab-primary transition hover:text-mvhab-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary">
                        {{ $cta }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</article>
