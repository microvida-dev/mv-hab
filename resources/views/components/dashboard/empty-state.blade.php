@props([
    'title' => 'Sem dados',
    'description' => 'Não existem dados para apresentar neste momento.',
])

<div class="rounded-md border border-dashed border-ink-200 bg-ink-50 px-5 py-6 text-sm">
    <p class="font-semibold text-ink-900">{{ $title }}</p>
    <p class="mt-1 text-ink-500">{{ $description }}</p>
</div>
