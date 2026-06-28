@php
    $authorized = $authorized ?? true;
    $icon = $icon ?? null;
    $title = $title ?? '';
    $description = $description ?? '';
    $href = $href ?? null;
    $metric = $metric ?? null;
    $status = $status ?? null;
    $actionLabel = $actionLabel ?? 'Abrir';

    $cardClasses = $authorized
        ? 'mv-card-interactive group flex h-full flex-col justify-between gap-4 p-5'
        : 'mv-card flex h-full flex-col justify-between gap-4 p-5 opacity-70';

    $content = trim((string) $slot);
@endphp

<article {{ $attributes->merge(['class' => $cardClasses]) }}>
    <div class="flex items-start gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-civic-50 text-civic-800">
            @if ($icon)
                <x-ui-icon :name="$icon" class="h-5 w-5" />
            @else
                <span class="text-sm font-bold">{{ mb_substr($title, 0, 2) }}</span>
            @endif
        </span>

        <div class="min-w-0 flex-1">
            <h3 class="text-base font-semibold text-ink-900">{{ $title }}</h3>

            @if ($description)
                <p class="mt-2 text-sm leading-5 text-ink-500">{{ $description }}</p>
            @endif

            @if ($status || $metric)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($status)
                        <x-ui.status-badge status="info" :label="$status" />
                    @endif

                    @if ($metric)
                        <span class="rounded-md bg-ink-50 px-2 py-1 text-xs font-semibold text-ink-700">{{ $metric }}</span>
                    @endif
                </div>
            @endif

            @if ($content !== '')
                <div class="mt-3 text-sm text-ink-500">{{ $slot }}</div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between gap-3">
        @isset($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endisset

        @if ($authorized && $href)
            <a href="{{ $href }}" class="inline-flex items-center gap-2 rounded-md text-sm font-semibold text-civic-700 transition hover:text-civic-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-civic-500 focus-visible:ring-offset-2">
                <span>{{ $actionLabel }}</span>
                <x-ui-icon name="arrow" class="h-4 w-4" />
            </a>
        @elseif (! $authorized)
            <span class="text-xs font-semibold text-ink-500">Sem autorização</span>
        @endif
    </div>
</article>
