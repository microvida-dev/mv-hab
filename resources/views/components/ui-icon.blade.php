@props([
    'name' => 'circle',
])

@php
    $paths = [
        'dashboard' => 'M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-5H4v5Zm10-9h6V4h-6v7Z',
        'users' => 'M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11ZM8 11c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11Zm0 2c-2.67 0-5 1.34-5 3v1.25C3 18.22 3.78 19 4.75 19h6.5c.97 0 1.75-.78 1.75-1.75V16c0-1.66-2.33-3-5-3Zm8 0c-.7 0-1.36.09-1.95.26.62.72.95 1.55.95 2.42v1.57c0 .62-.16 1.21-.44 1.75h4.69c.97 0 1.75-.78 1.75-1.75V16c0-1.66-2.33-3-5-3Z',
        'home' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z',
        'file' => 'M7 3h7l5 5v13H7V3Zm7 1.5V9h4.5M9 13h8M9 17h8',
        'wallet' => 'M4 6h15a1 1 0 0 1 1 1v3h-5a3 3 0 0 0 0 6h5v3a1 1 0 0 1-1 1H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Zm11 6h6v2h-6a1 1 0 0 1 0-2Z',
        'tool' => 'M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5l-2.8 2.8-2-2 2.8-2.8Z',
        'document' => 'M6 3h9l4 4v14H6V3Zm8 1.5V8h3.5M9 12h7M9 16h7',
        'plus' => 'M12 5v14M5 12h14',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'close' => 'M6 6l12 12M18 6 6 18',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0',
        'check' => 'm5 12 4 4L19 6',
        'alert' => 'M12 3 2 21h20L12 3Zm0 6v5m0 3h.01',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="{{ $paths[$name] ?? $paths['dashboard'] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
</svg>
