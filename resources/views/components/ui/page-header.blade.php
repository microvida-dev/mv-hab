@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 md:flex-row md:items-center md:justify-between']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-sm font-semibold text-civic-700">{{ $eyebrow }}</p>
        @endif

        <h1 class="mt-1 text-2xl font-semibold leading-tight text-ink-900">{{ $title }}</h1>

        @if ($description)
            <p class="mt-1 max-w-3xl text-sm leading-5 text-ink-500">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
