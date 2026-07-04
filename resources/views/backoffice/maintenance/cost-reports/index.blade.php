<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Relatórios de custos"
            description="Resumo agregado dos custos de manutenção por dimensão operacional."
        />
    </x-slot>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($summary as $title => $rows)
            <x-mv.section :title="str($title)->replace('_', ' ')->title()">
                @foreach ($rows as $row)
                    <p class="mt-2 text-sm">
                        {{ $row->housingUnit?->code ?? $row->supplier?->name ?? $row->name ?? 'Sem detalhe' }} · {{ number_format((float) $row->total, 2, ',', '.') }} EUR
                    </p>
                @endforeach
            </x-mv.section>
        @endforeach
    </div>
</x-app-layout>
