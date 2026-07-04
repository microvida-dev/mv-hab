<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Vistoria"
            :title="$propertyInspection->inspection_number"
            :description="$propertyInspection->housingUnit?->code"
        />
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-3 md:grid-cols-4">
            <x-mv.stat-card label="Tipo" :value="$propertyInspection->inspection_type->label()" />
            <x-mv.stat-card label="Estado" :value="$propertyInspection->status->label()" />
            <x-mv.stat-card label="Habitação" :value="$propertyInspection->housingUnit?->code ?? '-'" />
            <x-mv.stat-card label="Condição" :value="$propertyInspection->general_condition?->label() ?? '-'" />
        </div>

        <x-mv.section title="Resumo">
            <p class="text-sm leading-6 text-ink-700">{{ $propertyInspection->summary }}</p>
            <p class="mt-2 text-sm text-ink-500">{{ $propertyInspection->recommendations }}</p>
        </x-mv.section>

        <form method="POST" action="{{ route('backoffice.inspections.complete', $propertyInspection) }}" class="space-y-6">
            @csrf

            <x-mv.section title="Concluir vistoria">
                <div class="grid gap-3">
                    <select class="mv-input" name="general_condition">
                        <option value="good">Boa</option>
                        <option value="acceptable">Aceitável</option>
                        <option value="requires_repair">Requer reparação</option>
                        <option value="poor">Má</option>
                        <option value="critical">Crítica</option>
                    </select>
                    <textarea class="mv-input" name="summary" placeholder="Resumo da vistoria" required></textarea>
                    <textarea class="mv-input" name="recommendations" placeholder="Recomendações"></textarea>
                    <button class="mv-button-secondary">Concluir vistoria</button>
                </div>
            </x-mv.section>
        </form>

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('backoffice.inspections.validate', $propertyInspection) }}">
                @csrf
                <button class="mv-button-secondary">Validar</button>
            </form>
            <form method="POST" action="{{ route('backoffice.inspections.reports.generate', $propertyInspection) }}">
                @csrf
                <button class="mv-button-primary">Gerar auto</button>
            </form>
        </div>

        <x-mv.section title="Checklist">
            @foreach ($propertyInspection->items as $item)
                <p class="mt-2 text-sm">{{ $item->label }} · {{ $item->condition?->label() ?? 'Sem avaliação' }}</p>
            @endforeach
        </x-mv.section>
    </div>
</x-app-layout>
