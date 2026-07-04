<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Património"
            title="Manutenção e vistorias"
            description="Resumo operacional dos pedidos de manutenção, urgência, custos e vistorias associadas."
        />
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-4">
            @foreach (['new' => 'Novos', 'under_review' => 'Em análise', 'scheduled' => 'Agendados', 'in_progress' => 'Em execução', 'resolved' => 'Resolvidos', 'rejected' => 'Rejeitados', 'closed' => 'Fechados'] as $status => $label)
                <x-mv.stat-card :label="$label" :value="$metrics['by_status'][$status] ?? 0" />
            @endforeach
            <x-mv.stat-card label="Urgentes" :value="$metrics['urgent_count']" />
            <x-mv.stat-card label="Emergências" :value="$metrics['emergency_count']" />
            <x-mv.stat-card label="Tempo médio" :value="number_format((float) $metrics['average_resolution_hours'], 1, ',', '.').' h'" />
            <x-mv.stat-card label="Custo total" :value="number_format((float) $metrics['total_cost'], 2, ',', '.').' EUR'" />
        </div>

        <div class="flex flex-wrap gap-3">
            <a class="mv-button-primary" href="{{ route('backoffice.maintenance.requests.index') }}">Pedidos</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.inspections.index') }}">Vistorias</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.maintenance.cost-reports.index') }}">Relatórios de custos</a>
        </div>
    </div>
</x-app-layout>
