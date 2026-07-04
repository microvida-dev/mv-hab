@props([
    'label',
    'detail' => null,
    'passed' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-ink-100 p-4']) }}>
    <div class="flex gap-3">
        <span @class([
            'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-2xl text-xs font-semibold',
            'bg-mvhab-surface text-mvhab-primary' => $passed,
            'bg-red-50 text-red-700' => ! $passed,
        ])>
            {{ $passed ? '✓' : '!' }}
        </span>

        <div>
            <p class="font-semibold text-ink-900">{{ $label }}</p>

            @if ($detail)
                <p class="mt-1 text-sm text-ink-500">{{ $detail }}</p>
            @endif
        </div>
    </div>
    {{ $slot }}
</div>
