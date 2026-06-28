@props([
    'title' => 'Sem dados analíticos',
    'description' => 'Não existem dados agregados para os filtros selecionados.',
])

<x-ui.empty-state :title="$title" :description="$description" icon="chart" {{ $attributes }} />
