<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Resposta ao aperfeiçoamento</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $response->correctionRequest->request_number }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface p-6">
                <p class="text-sm text-ink-500">{{ $response->correctionRequestItem->title }}</p>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $response->response_text }}</p>
                @if ($response->documentSubmission)
                    <p class="mt-3 text-sm text-ink-600">Documento associado: {{ $response->documentSubmission->title ?? $response->documentSubmission->original_filename }}</p>
                @endif
                <p class="mt-3 text-xs text-ink-500">{{ $response->status->label() }} · submetida em {{ $response->submitted_at?->format('d/m/Y H:i') }}</p>
            </section>
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Analisar resposta</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <form method="POST" action="{{ route('backoffice.correction-responses.accept', $response) }}" class="space-y-3">@csrf<textarea name="review_notes" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Notas"></textarea><button class="mv-button-primary">Aceitar</button></form>
                    <form method="POST" action="{{ route('backoffice.correction-responses.reject', $response) }}" class="space-y-3">@csrf<textarea name="review_notes" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Notas"></textarea><button class="mv-button-secondary">Rejeitar</button></form>
                    <form method="POST" action="{{ route('backoffice.correction-responses.request-more-information', $response) }}" class="space-y-3">@csrf<textarea name="review_notes" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Notas"></textarea><button class="mv-button-secondary">Pedir mais informação</button></form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
