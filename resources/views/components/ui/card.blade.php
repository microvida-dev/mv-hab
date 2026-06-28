@props([
    'padding' => 'p-5',
    'interactive' => false,
    'muted' => false,
])

@php
    $baseClass = $interactive ? 'mv-card-interactive' : ($muted ? 'mv-card-muted' : 'mv-card');
@endphp

<div {{ $attributes->merge(['class' => $baseClass.' '.$padding]) }}>
    {{ $slot }}
</div>
