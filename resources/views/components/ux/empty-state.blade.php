@props([
    'title' => 'Sem dados disponíveis',
    'description' => 'Não existem elementos autorizados para apresentar neste momento.',
])

<x-ui.empty-state :title="$title" :description="$description" {{ $attributes }} />
