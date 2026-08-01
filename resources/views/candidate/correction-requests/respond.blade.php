<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-semibold text-ink-900">
            Responder a aperfeiçoamento
        </h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="mv-surface p-6">
                <p class="text-sm text-ink-600">
                    {{ $correctionRequest->subject }}
                </p>

                <form
                    method="POST"
                    action="{{ route('candidate.correction-requests.responses.store', $correctionRequest) }}"
                    class="mt-5 space-y-4"
                >
                    @csrf

                    <select
                        name="correction_request_item_id"
                        required
                        class="block w-full rounded-md border-ink-200"
                    >
                        @foreach ($correctionRequest->items as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->title }}
                            </option>
                        @endforeach
                    </select>

                    <textarea
                        name="response_text"
                        rows="7"
                        required
                        class="block w-full rounded-md border-ink-200"
                    >{{ old('response_text', $response->response_text ?? '') }}</textarea>

                    @if (isset($documents) && $documents->isNotEmpty())
                        <select
                            name="document_submission_id"
                            class="block w-full rounded-md border-ink-200"
                        >
                            <option value="">Sem documento associado</option>
                            @foreach ($documents as $document)
                                <option value="{{ $document->id }}">
                                    {{ $document->title ?: $document->original_filename }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <button class="mv-button-primary">
                        Submeter resposta
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
