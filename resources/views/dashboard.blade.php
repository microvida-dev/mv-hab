@php
    $metricIcons = [
        'Munícipes' => 'users',
        'Agregados familiares' => 'users',
        'Habitações' => 'home',
        'Candidaturas' => 'file',
        'Contratos ativos' => 'document',
        'Pagamentos recebidos' => 'wallet',
        'Pedidos de manutenção abertos' => 'tool',
    ];

    $quickActions = [
        [
            'label' => 'Novo munícipe',
            'description' => 'Criar registo base para atendimento municipal.',
            'route' => 'citizens.create',
            'icon' => 'users',
        ],
        [
            'label' => 'Nova candidatura',
            'description' => 'Abrir candidatura no fluxo CRM atual.',
            'route' => 'applications.create',
            'icon' => 'file',
        ],
        [
            'label' => 'Registar habitação',
            'description' => 'Adicionar fogo ao parque habitacional.',
            'route' => 'housing-units.create',
            'icon' => 'home',
        ],
        [
            'label' => 'Pedido de manutenção',
            'description' => 'Criar pedido operacional para um imóvel.',
            'route' => 'maintenance-requests.create',
            'icon' => 'tool',
        ],
    ];

    $workQueues = [
        ['label' => 'Candidaturas em curso', 'route' => 'applications.index', 'icon' => 'file'],
        ['label' => 'Documentos submetidos', 'route' => 'documents.index', 'icon' => 'document'],
        ['label' => 'Pagamentos e vencimentos', 'route' => 'payments.index', 'icon' => 'wallet'],
        ['label' => 'Manutenção aberta', 'route' => 'maintenance-requests.index', 'icon' => 'tool'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Operação municipal</p>
                <h1 class="mt-1 text-2xl font-semibold leading-tight text-ink-900">Dashboard</h1>
                <p class="mt-1 max-w-2xl text-sm text-ink-500">Visão geral do CRM atual e da preparação para a plataforma processual MV HAB.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('citizens.create') }}" class="mv-button-secondary">
                    <x-ui-icon name="plus" class="h-4 w-4" />
                    <span>Novo munícipe</span>
                </a>
                <a href="{{ route('applications.create') }}" class="mv-button-primary">
                    <x-ui-icon name="plus" class="h-4 w-4" />
                    <span>Nova candidatura</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($metrics as $metric)
                        <x-stat-card
                            :label="$metric['label']"
                            :value="$metric['value']"
                            :description="$metric['description']"
                            :currency="$metric['currency'] ?? false"
                            :icon="$metricIcons[$metric['label']] ?? null"
                        />
                    @endforeach
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink-900">Ações rápidas</h2>
                            <p class="mt-1 text-sm text-ink-500">Entradas frequentes do backoffice atual.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @foreach ($quickActions as $action)
                            <a href="{{ route($action['route']) }}" class="mv-surface flex min-h-28 items-start gap-4 p-4 transition hover:border-civic-100 hover:bg-civic-50/40">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                                    <x-ui-icon :name="$action['icon']" class="h-5 w-5" />
                                </span>
                                <span>
                                    <span class="block text-sm font-semibold text-ink-900">{{ $action['label'] }}</span>
                                    <span class="mt-1 block text-sm leading-5 text-ink-500">{{ $action['description'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="mv-surface overflow-hidden">
                        <div class="border-b border-ink-100 px-5 py-4">
                            <h2 class="text-base font-semibold text-ink-900">Filas de trabalho</h2>
                        </div>
                        <div class="divide-y divide-ink-100">
                            @foreach ($workQueues as $queue)
                                <a href="{{ route($queue['route']) }}" class="flex items-center gap-3 px-5 py-4 transition hover:bg-ink-50">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-ink-50 text-ink-700">
                                        <x-ui-icon :name="$queue['icon']" class="h-4 w-4" />
                                    </span>
                                    <span class="text-sm font-semibold text-ink-900">{{ $queue['label'] }}</span>
                                    <x-ui-icon name="arrow" class="ms-auto h-4 w-4 text-ink-500" />
                                </a>
                            @endforeach
                        </div>
                    </section>

                    <section class="mv-surface p-5">
                        <h2 class="text-base font-semibold text-ink-900">Fundação técnica</h2>
                        <div class="mt-4 space-y-3 text-sm text-ink-600">
                            <div class="flex gap-3">
                                <x-ui-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-civic-700" />
                                <p>RBAC e auditoria inicial preparados na Sprint 1.</p>
                            </div>
                            <div class="flex gap-3">
                                <x-ui-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-civic-700" />
                                <p>UX/UI base organizada para evolução gradual dos módulos existentes.</p>
                            </div>
                            <div class="flex gap-3">
                                <x-ui-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-signal-700" />
                                <p>Policies ainda não estão aplicadas nos controllers até existir atribuição de roles.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
