<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Processo administrativo</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $process->process_number }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $process->application->application_number }}</p>
            </div>
            <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $process->status->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="mv-surface p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-ink-900">{{ $publicStatus?->title ?? 'Acompanhamento' }}</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $publicStatus?->description ?? 'Pode acompanhar aqui o estado administrativo da candidatura e responder a pedidos emitidos pelos serviços municipais.' }}</p>
                    </div>
                    <div class="min-w-48 rounded-md bg-ink-50 p-4 text-sm">
                        <p class="font-semibold text-ink-900">{{ $publicStatus?->progress_percentage ?? 0 }}% concluído</p>
                        <p class="mt-1 text-ink-500">{{ $publicStatus?->action_required ? 'Ação necessária' : 'Sem ação imediata' }}</p>
                        @if ($publicStatus?->action_due_at)
                            <p class="mt-1 text-ink-500">Prazo {{ $publicStatus->action_due_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </div>
                @if ($publicStatus?->next_step)
                    <p class="mt-4 rounded-md bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">Próximo passo: {{ $publicStatus->next_step }}</p>
                @endif
                <a href="{{ route('candidate.processes.timeline', $process) }}" class="mt-4 inline-flex text-sm font-semibold text-civic-700">Ver timeline completa</a>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Ações disponíveis</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse ($actions as $action)
                        <div class="rounded-md border border-ink-100 p-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $action['title'] }}</p>
                            @if ($action['description'])
                                <p class="mt-1 text-ink-600">{{ $action['description'] }}</p>
                            @endif
                            @if ($action['due_at'])
                                <p class="mt-2 text-xs font-semibold text-ink-500">Prazo {{ $action['due_at']->format('d/m/Y H:i') }}</p>
                            @endif
                            @if ($action['route'])
                                <a href="{{ $action['route'] }}" class="mt-3 inline-flex text-sm font-semibold text-civic-700">Abrir ação</a>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Não existem ações pendentes neste momento.</p>
                    @endforelse
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Pedidos de aperfeiçoamento</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @forelse ($process->correctionRequests as $request)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-4 text-sm">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $request->subject }}</p>
                                <p class="mt-1 text-ink-500">{{ $request->status->label() }} · prazo {{ $request->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>
                            <a href="{{ route('candidate.correction-requests.show', $request) }}" class="font-semibold text-civic-700">Consultar</a>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-ink-500">Sem pedidos emitidos.</p>
                    @endforelse
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Cronologia resumida</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($timeline as $event)
                        <div class="rounded-md border border-ink-100 p-4 text-sm">
                            <p class="text-xs font-semibold uppercase text-ink-500">{{ $event['date']?->format('d/m/Y H:i') }}</p>
                            <p class="mt-1 font-semibold text-ink-900">{{ $event['title'] }}</p>
                            @if ($event['description'])
                                <p class="mt-1 text-ink-600">{{ $event['description'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Ainda não existem eventos registados.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
