@props([
    'workspace',
])

<div class="space-y-6">
    <x-cases.enterprise.header :summary="$workspace['summary']" />

    <x-cases.contextual-search
        :query="$workspace['contextual_search_query']"
        :results="$workspace['search_results']"
    />

    <x-cases.case-tabs :tabs="$workspace['tabs']" />

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            {{ $slot }}
        </div>

        <aside class="space-y-6">
            <x-cases.enterprise.sidebar :workspace="$workspace" />
        </aside>
    </div>
</div>
