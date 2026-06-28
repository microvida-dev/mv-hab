@props([
    'groups' => [],
])

<section {{ $attributes->merge(['class' => 'mv-card']) }}>
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="O Meu Trabalho"
            description="Trabalho agregado do utilizador e da sua equipa, ordenado por prioridade e prazo."
        />
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($groups as $group)
            <div class="p-5">
                <h3 class="text-sm font-semibold text-ink-900">{{ $group['title'] }}</h3>
                <div class="mt-3 space-y-2">
                    @foreach (($group['items'] ?? []) as $item)
                        <a href="{{ $item['url'] }}" class="flex items-center justify-between gap-3 rounded-md border border-ink-100 px-3 py-2 text-sm hover:border-civic-200 hover:bg-civic-50 focus:outline-none focus:ring-2 focus:ring-civic-500">
                            <span class="min-w-0 truncate font-medium text-ink-800">{{ $item['title'] }}</span>
                            <span class="flex shrink-0 items-center gap-2">
                                <x-ui.status-badge :status="$item['priority']" :label="$item['priority_label']" />
                                <x-productivity.deadline-indicator :deadline="$item['deadline'] ?? null" />
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem trabalho atribuído"
                    description="Não existem pendências autorizadas associadas ao utilizador ou equipa."
                />
            </div>
        @endforelse
    </div>
</section>
