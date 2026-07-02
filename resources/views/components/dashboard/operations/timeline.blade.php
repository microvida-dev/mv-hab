@props([
    'groups' => [],
    'fallbackItems' => [],
])

<div class="mt-6 space-y-6">
    @forelse($groups as $group)
        <x-dashboard.operations.timeline-group :group="$group" />
    @empty
        <div class="divide-y divide-ink-100">
            @forelse($fallbackItems as $item)
                <x-dashboard.operations.timeline-item :item="$item" :show-time="false" />
            @empty
                <x-ui.empty-state
                    title="Sem atividades para hoje"
                    description="Não existem tarefas ou prazos autorizados."
                />
            @endforelse
        </div>
    @endforelse
</div>
