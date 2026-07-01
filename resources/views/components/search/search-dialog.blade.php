@props([
    'title' => 'Pesquisa Universal',
])

<div
    {{ $attributes->merge(['class' => 'rounded-2xl border border-ink-200 bg-mvhab-card shadow-lg']) }}
    role="dialog"
    aria-label="{{ $title }}"
>
    <div class="border-b border-ink-100 px-5 py-4">
        <h2 class="text-base font-semibold text-ink-900">{{ $title }}</h2>
    </div>
    <div class="p-5">
        {{ $slot }}
    </div>
</div>
