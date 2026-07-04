@props([
    'striped' => false,
])

<div {{ $attributes->class([
    'mv-surface overflow-hidden',
]) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-ink-100 text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
