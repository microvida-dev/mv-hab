@props([
    'tabs' => [],
    'active' => null,
    'ariaLabel' => 'Separadores',
])

<nav {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl border border-ink-100 bg-mvhab-card shadow-surface']) }} aria-label="{{ $ariaLabel }}">
    <div class="flex min-w-max gap-1 p-2" role="tablist">
        @foreach ($tabs as $tab)
            @php
                $key = $tab['key'] ?? $loop->index;
                $isActive = $active !== null && $active === $key;
                $href = $tab['href'] ?? '#'.$key;
            @endphp

            <a
                href="{{ $href }}"
                class="mv-tab {{ $isActive ? 'mv-tab-active' : '' }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
