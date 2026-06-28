@props([
    'items' => [],
])

<x-ui.card id="case-tab-summary">
    <x-ui.section-header
        title="Resumo processual"
        description="Leitura agregada, autorizada e minimizada do caso."
    />

    <x-ui.data-list class="mt-5" :items="$items" />
</x-ui.card>
