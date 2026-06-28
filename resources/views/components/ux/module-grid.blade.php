@props([
    'items' => [],
    'emptyTitle' => 'Sem módulos disponíveis',
    'emptyDescription' => 'Não existem módulos autorizados para apresentar.',
])

<div {{ $attributes->merge(['class' => 'grid gap-4 md:grid-cols-2 xl:grid-cols-3']) }}>
    @forelse ($items as $item)
        <x-ux.module-card
            :title="$item['title'] ?? $item['label'] ?? 'Módulo'"
            :description="$item['description'] ?? null"
            :href="$item['url'] ?? null"
            :icon="$item['icon'] ?? null"
            :status="$item['status'] ?? null"
            :metric="$item['metric'] ?? null"
            :action-label="$item['action_label'] ?? 'Abrir'"
            :authorized="$item['authorized'] ?? true"
        />
    @empty
        <x-ui.empty-state :title="$emptyTitle" :description="$emptyDescription" />
    @endforelse
</div>
