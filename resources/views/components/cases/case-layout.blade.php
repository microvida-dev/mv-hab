@props([
    'workspace',
])

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-6">
        {{ $slot }}
    </div>

    <aside class="space-y-6">
        <x-cases.case-sidebar :workspace="$workspace" />
    </aside>
</div>
