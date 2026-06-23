<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Manutenção e vistorias</h1></x-slot>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-4">
            @foreach (['new' => 'Novos', 'under_review' => 'Em análise', 'scheduled' => 'Agendados', 'in_progress' => 'Em execução', 'resolved' => 'Resolvidos', 'rejected' => 'Rejeitados', 'closed' => 'Fechados'] as $status => $label)
                <div class="mv-card"><p class="text-xs text-ink-500">{{ $label }}</p><p class="mt-1 text-2xl font-semibold">{{ $metrics['by_status'][$status] ?? 0 }}</p></div>
            @endforeach
            <div class="mv-card"><p class="text-xs text-ink-500">Urgentes</p><p class="mt-1 text-2xl font-semibold">{{ $metrics['urgent_count'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Emergências</p><p class="mt-1 text-2xl font-semibold">{{ $metrics['emergency_count'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Tempo médio</p><p class="mt-1 text-2xl font-semibold">{{ number_format((float) $metrics['average_resolution_hours'], 1, ',', '.') }} h</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Custo total</p><p class="mt-1 text-2xl font-semibold">{{ number_format((float) $metrics['total_cost'], 2, ',', '.') }} EUR</p></div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="mv-button-primary" href="{{ route('backoffice.maintenance.requests.index') }}">Pedidos</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.inspections.index') }}">Vistorias</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.maintenance.cost-reports.index') }}">Relatórios de custos</a>
        </div>
    </div>
</x-app-layout>
