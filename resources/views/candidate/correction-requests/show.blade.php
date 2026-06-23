<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Pedido de aperfeiçoamento</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $correctionRequest->request_number }}</h1>
            </div>
            <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $correctionRequest->status->label() }}</span>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">{{ $correctionRequest->subject }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $correctionRequest->message }}</p>
                <p class="mt-3 text-sm font-semibold text-ink-700">Prazo: {{ $correctionRequest->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</p>
                @if ($correctionRequest->response_deadline_at?->isPast())
                    <p class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">O prazo de resposta a este pedido encontra-se vencido. Poderá contactar os serviços municipais para esclarecimentos.</p>
                @endif
                @if ($correctionRequest->isOpenForCandidateResponse())
                    <a href="{{ route('candidate.correction-requests.respond', $correctionRequest) }}" class="mt-4 inline-flex mv-button-primary">Responder</a>
                @endif
            </section>
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Itens solicitados</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($correctionRequest->items as $item)
                        <div class="py-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $item->title }}</p>
                            <p class="mt-1 text-ink-600">{{ $item->description }}</p>
                            <p class="mt-1 text-xs text-ink-500">{{ $item->required_action->label() }} · {{ $item->status->label() }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Histórico de respostas</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @forelse ($correctionRequest->responses as $response)
                        <div class="py-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $response->status->label() }}</p>
                            <p class="mt-1 text-ink-600">{{ $response->response_text }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-ink-500">Ainda não submeteu resposta.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
