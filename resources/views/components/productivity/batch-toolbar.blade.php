@props([
    'toolbar' => ['actions' => []],
])

<section {{ $attributes->merge(['class' => 'mv-card']) }} x-data="{ selected: 0 }">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="Seleção em lote"
            description="Infraestrutura visual preparada. Ações críticas só aparecem quando existir fluxo autorizado."
        />
    </div>

    <div class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-ink-700">
            <input type="checkbox" class="rounded border-ink-300 text-mvhab-primary focus:ring-2 focus:ring-civic-500" x-on:change="selected = $event.target.checked ? 1 : 0">
            Selecionar item visível
        </label>
        <p class="text-sm text-ink-500"><span x-text="selected"></span> selecionado(s)</p>
        <div class="flex flex-wrap gap-2">
            @foreach (($toolbar['actions'] ?? []) as $action)
                <x-ui.action-button :disabled="! ($action['enabled'] ?? false)">
                    {{ $action['label'] }}
                </x-ui.action-button>
            @endforeach
        </div>
    </div>

    <div class="border-t border-ink-100 px-5 py-3 text-xs text-ink-500">
        Ações destrutivas indisponíveis nesta camada.
    </div>
</section>
