<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">
                Área pessoal
            </p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                Pedidos de aperfeiçoamento
            </h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="mv-surface p-6">
                <p class="text-sm leading-6 text-ink-600">
                    Os serviços municipais solicitaram informação adicional
                    ou correção de elementos da sua candidatura. Prepare
                    apenas os elementos indicados e efetue uma única
                    submissão formal dentro do prazo.
                </p>
            </section>

            <div class="space-y-4">
                @forelse ($requests as $request)
                    @php
                        $progress = $progressByRequest[$request->id] ?? [
                            'completed' => 0,
                            'total' => 0,
                            'pending' => 0,
                            'percentage' => 0,
                            'overdue' => false,
                            'due_soon' => false,
                            'formal_submitted' => false,
                        ];
                    @endphp

                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-ink-900">
                                    {{ $request->request_number }}
                                </p>
                                <p class="mt-1 text-sm text-ink-600">
                                    Candidatura
                                    {{ $request->application->application_number }}
                                </p>
                            </div>

                            <span class="rounded-full bg-ink-100 px-3 py-1 text-xs font-semibold text-ink-700">
                                {{ $request->status->label() }}
                            </span>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                            <div>
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="font-semibold text-ink-700">
                                        Progresso da checklist
                                    </span>
                                    <span class="font-semibold text-civic-700">
                                        {{ $progress['completed'] }}
                                        /
                                        {{ $progress['total'] }}
                                    </span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-100">
                                    <div
                                        class="h-full rounded-full bg-civic-700"
                                        style="width: {{ $progress['percentage'] }}%"
                                    ></div>
                                </div>
                                <p class="mt-2 text-xs text-ink-500">
                                    {{ $progress['pending'] }}
                                    elemento(s) por preparar
                                    · prazo
                                    {{ $request->response_deadline_at?->format('d/m/Y H:i') ?? 'não definido' }}
                                </p>
                            </div>

                            <a
                                href="{{ route('candidate.correction-requests.show', $request) }}"
                                class="mv-button-primary"
                            >
                                Consultar pedido
                            </a>
                        </div>

                        @if ($progress['overdue'])
                            <p class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                                O prazo deste pedido encontra-se vencido.
                            </p>
                        @elseif ($progress['due_soon'])
                            <p class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                                O prazo termina nas próximas 48 horas.
                            </p>
                        @elseif ($progress['formal_submitted'])
                            <p class="mt-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">
                                A submissão formal foi registada.
                            </p>
                        @endif
                    </article>
                @empty
                    <section class="mv-surface p-8 text-center">
                        <p class="text-sm text-ink-500">
                            Não existem pedidos visíveis.
                        </p>
                    </section>
                @endforelse
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
