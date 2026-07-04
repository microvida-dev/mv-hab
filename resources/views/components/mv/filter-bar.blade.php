@props([
    'method' => 'GET',
    'action' => null,
    'columns' => 4,
    'compact' => false,
])

@php
    $gridClass = match ((int) $columns) {
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-2 lg:grid-cols-3',
        5 => 'sm:grid-cols-2 lg:grid-cols-5',
        6 => 'sm:grid-cols-2 lg:grid-cols-6',
        7 => 'sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7',
        default => 'sm:grid-cols-2 lg:grid-cols-4',
    };
@endphp

<form
    method="{{ $method }}"
    @if ($action) action="{{ $action }}" @endif
    {{ $attributes->class([
        'mv-surface',
        $compact ? 'p-4' : 'p-5',
    ]) }}
>
    <div @class([
        'grid gap-4',
        $gridClass,
    ])>
        {{ $slot }}
    </div>

    @if ($actions ?? false)
        <div class="mt-4">
            <x-mv.action-bar>
                {{ $actions }}
            </x-mv.action-bar>
        </div>
    @endif
</form>
