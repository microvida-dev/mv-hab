@props([
    'widgets' => [],
])

<section class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Foco do perfil" />
    </div>
    <div class="divide-y divide-ink-100">
        @forelse ($widgets as $widget)
            <div class="px-5 py-4">
                <p class="text-sm font-semibold text-ink-900">{{ $widget['title'] }}</p>
                <p class="mt-1 text-sm text-ink-500">{{ $widget['description'] }}</p>
            </div>
        @empty
            <div class="p-5">
                <x-dashboard.empty-state
                    title="Sem widgets específicos"
                    description="O perfil atual não tem widgets operacionais adicionais configurados."
                />
            </div>
        @endforelse
    </div>
</section>
