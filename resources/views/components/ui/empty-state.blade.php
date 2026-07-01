@props([
    'title' => 'Sem dados',
    'description' => 'Não existem dados para apresentar neste momento.',
    'icon' => 'alert',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-ink-200 bg-ink-50 px-5 py-6 text-sm']) }}>
    <div class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-mvhab-card text-ink-600">
            <x-ui-icon :name="$icon" class="h-4 w-4" />
        </span>
        <div>
            <p class="font-semibold text-ink-900">{{ $title }}</p>
            <p class="mt-1 leading-5 text-ink-500">{{ $description }}</p>
        </div>
    </div>
</div>
