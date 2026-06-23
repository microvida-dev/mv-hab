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
                <p class="mt-3 text-sm text-ink-500">Prazo: {{ $correctionRequest->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</p>
                @if ($correctionRequest->status === \App\Enums\CorrectionRequestStatus::Draft)
                    <form method="POST" action="{{ route('backoffice.correction-requests.issue', $correctionRequest) }}" class="mt-4 flex flex-wrap items-center gap-3">
                        @csrf
                        <label class="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="confirm_issue" value="1" class="rounded border-ink-300">Ao emitir este pedido, o candidato passará a poder responder através da sua área pessoal.</label>
                        <button class="mv-button-primary">Emitir pedido</button>
                    </form>
                @endif
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Itens solicitados</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($correctionRequest->items as $item)
                        <div class="py-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $item->title }}</p>
                            <p class="mt-1 text-ink-600">{{ $item->description }}</p>
                            <p class="mt-1 text-xs text-ink-500">{{ $item->issue_type->label() }} · {{ $item->required_action->label() }} · {{ $item->status->label() }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Respostas do candidato</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @forelse ($correctionRequest->responses as $response)
                        <div class="py-4 text-sm">
                            <a href="{{ route('backoffice.correction-responses.show', $response) }}" class="font-semibold text-civic-700">{{ $response->correctionRequestItem->title }}</a>
                            <p class="mt-1 text-ink-600">{{ $response->response_text }}</p>
                            <p class="mt-1 text-xs text-ink-500">{{ $response->status->label() }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-ink-500">Sem respostas submetidas.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
