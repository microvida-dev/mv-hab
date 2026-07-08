@props([
    'widgets' => [],
])

@php
    $widgetsCollection = collect($widgets);
@endphp

<section class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="Foco do perfil"
            description="{{ $widgetsCollection->count() }} widget(s) operacional(is)"
        />
    </div>

    <div class="space-y-3 p-5">
        @forelse ($widgetsCollection as $widget)
            <x-dashboard.widgets.intelligent-card :widget="$widget" />
        @empty
            <x-dashboard.empty-state
                title="Sem widgets específicos"
                description="O perfil atual não tem widgets operacionais adicionais configurados."
            />
        @endforelse
    </div>
</section>
