@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 rounded-2xl bg-mvhab-surface px-3 py-2 text-sm font-semibold text-mvhab-primary ring-1 ring-mvhab-support/40 transition'
            : 'flex items-center gap-3 rounded-2xl px-3 py-2 text-sm font-medium text-ink-500 transition hover:bg-ink-50 hover:text-ink-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
