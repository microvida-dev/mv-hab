<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Exploração pós-atribuição</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="mv-card"><p class="text-xs text-ink-500">Inquilinos</p><p class="text-2xl font-semibold">{{ $metrics['total_tenants'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Contratos ativos</p><p class="text-2xl font-semibold">{{ $metrics['active_contracts'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Faturas ativas</p><p class="text-2xl font-semibold">{{ $metrics['active_invoices'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Faturas em atraso</p><p class="text-2xl font-semibold">{{ $metrics['overdue_invoices'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Pedidos manutenção</p><p class="text-2xl font-semibold">{{ $metrics['open_maintenance_requests'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Vistorias agendadas</p><p class="text-2xl font-semibold">{{ $metrics['scheduled_inspections'] }}</p></div>
        </div>
        <div class="mv-card">
            <p class="text-sm text-ink-600">As cobranças automáticas registadas nesta plataforma correspondem à geração operacional de valores a cobrar e não implicam, por si só, movimento bancário externo sem integração devidamente configurada.</p>
        </div>
        <div class="grid gap-3 md:grid-cols-5">
            <a class="mv-button-secondary" href="{{ route('backoffice.tenant-operations.invoices.index') }}">Faturas</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.tenant-operations.payments.index') }}">Pagamentos</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.tenant-operations.charge-runs.index') }}">Cobranças</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.tenant-operations.communications.index') }}">Comunicações</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.tenant-operations.maintenance-reports.index') }}">Relatórios</a>
        </div>
    </div>
</x-app-layout>
