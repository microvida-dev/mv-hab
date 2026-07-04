@props([
    'icon' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->class([
    'flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 bg-ink-50/60 px-6 py-10 text-center',
]) }}>
    @if ($icon)
        <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
            <x-mv-icon :name="$icon" class="h-6 w-6" aria-hidden="true" />
        </span>
    @endif

    <h3 class="text-sm font-semibold text-ink-900">
        {{ $title }}
    </h3>

    @if ($description)
        <p class="mt-2 max-w-xl text-sm leading-6 text-ink-500">
            {{ $description }}
        </p>
    @endif

    @if ($actions ?? false)
        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
            {{ $actions }}
        </div>
    @endif

    @if (trim($slot))
        <div class="mt-5">
            {{ $slot }}
        </div>
    @endif
</div>
