<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">
                    Aperfeiçoamento
                </p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    {{ $administrativeProcess->process_number }}
                </h1>
            </div>

            @can('createBackoffice', $administrativeProcess)
                <a
                    href="{{ route('backoffice.correction-requests.create', $administrativeProcess) }}"
                    class="mv-button-primary"
                >
                    Novo pedido
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Pedidos ativos
                    </p>
                    <p class="mt-2 text-3xl font-semibold text-ink-950">
                        {{ $requestSummary['active_requests'] }}
                    </p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Submetidos
                    </p>
                    <p class="mt-2 text-3xl font-semibold text-ink-950">
                        {{ $requestSummary['submitted_requests'] }}
                    </p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Expirados
                    </p>
                    <p class="mt-2 text-3xl font-semibold text-ink-950">
                        {{ $requestSummary['expired_requests'] }}
                    </p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Checklist concluída
                    </p>
                    <p class="mt-2 text-3xl font-semibold text-ink-950">
                        {{ $requestSummary['percentage'] }}%
                    </p>
                </div>
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Pedido</th>
                                <th class="px-5 py-3">Assunto</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Progresso</th>
                                <th class="px-5 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($requests as $request)
                                @php
                                    $progress = $progressByRequest[$request->id] ?? [
                                        'completed' => 0,
                                        'total' => 0,
                                        'percentage' => 0,
                                    ];
                                @endphp
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">
                                        {{ $request->request_number }}
                                    </td>
                                    <td class="px-5 py-4 text-ink-700">
                                        {{ $request->subject }}
                                    </td>
                                    <td class="px-5 py-4 text-ink-600">
                                        {{ $request->status->label() }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="min-w-36">
                                            <div class="flex justify-between text-xs text-ink-600">
                                                <span>
                                                    {{ $progress['completed'] }}
                                                    /
                                                    {{ $progress['total'] }}
                                                </span>
                                                <span>
                                                    {{ $progress['percentage'] }}%
                                                </span>
                                            </div>
                                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-ink-100">
                                                <div
                                                    class="h-full rounded-full bg-civic-700"
                                                    style="width: {{ $progress['percentage'] }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a
                                            class="font-semibold text-civic-700"
                                            href="{{ route('backoffice.correction-requests.show', $request) }}"
                                        >
                                            Consultar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-5 py-8 text-center text-ink-500"
                                    >
                                        Não existem pedidos neste processo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
