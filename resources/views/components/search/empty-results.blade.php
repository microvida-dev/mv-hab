@props([
    'term' => '',
])

<x-ui.empty-state
    icon="search"
    title="Sem resultados autorizados"
    :description="$term !== ''
        ? 'Não foram encontrados resultados autorizados para a pesquisa indicada.'
        : 'Introduza um termo para pesquisar recursos e comandos disponíveis.'"
/>
