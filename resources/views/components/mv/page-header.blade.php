@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        @if ($eyebrow)
            <p class="text-sm font-semibold text-mvhab-primary">{{ $eyebrow }}</p>
        @endif

        <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $title }}</h1>

        @if ($description)
            <p class="mt-1 text-sm text-ink-500">{{ $description }}</p>
        @endif
    </div>

    @if ($actions ?? false)
        <div class="flex flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
