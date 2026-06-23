@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex w-full items-center gap-3 rounded-md bg-civic-50 px-3 py-2 text-start text-sm font-semibold text-civic-900 ring-1 ring-civic-100 transition'
            : 'flex w-full items-center gap-3 rounded-md px-3 py-2 text-start text-sm font-medium text-ink-600 transition hover:bg-ink-50 hover:text-ink-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
