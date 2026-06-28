@props([
    'title' => 'Sem dados',
    'description' => 'Não existem dados autorizados para apresentar neste momento.',
])

<x-ui.empty-state :title="$title" :description="$description" />
