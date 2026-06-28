@props([
    'groups' => [],
])

<section {{ $attributes->merge(['class' => 'mv-card']) }} aria-live="polite">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="Caixa de Entrada Municipal"
            description="Notificações existentes agrupadas por categoria operacional autorizada."
        />
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($groups as $group)
            <div class="p-5">
                <h3 class="text-sm font-semibold text-ink-900">{{ $group['title'] }}</h3>
                <div class="mt-3 space-y-2">
                    @foreach (($group['items'] ?? []) as $item)
                        <a href="{{ $item['url'] }}" class="block rounded-md border border-ink-100 px-3 py-2 hover:border-civic-200 hover:bg-civic-50 focus:outline-none focus:ring-2 focus:ring-civic-500">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-ink-900">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-xs text-ink-500">{{ $item['type_label'] }} · {{ $item['suggested_action'] }}</p>
                                </div>
                                <x-ui.status-badge :status="$item['priority']" :label="$item['priority_label']" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem notificações autorizadas"
                    description="A caixa de entrada agrega apenas notificações já existentes e visíveis para o perfil atual."
                />
            </div>
        @endforelse
    </div>
</section>
