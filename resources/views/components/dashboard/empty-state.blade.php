@props([
    'title' => 'Sem dados',
    'description' => 'Não existem dados para apresentar neste momento.',
])

<x-ui.empty-state :title="$title" :description="$description" />
