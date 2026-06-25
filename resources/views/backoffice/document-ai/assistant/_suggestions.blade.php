<section class="mv-surface p-6">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-ink-900">Sugestões de aperfeiçoamento</h2>
        <p class="mt-1 text-sm text-ink-500">Rascunhos internos para apoio ao técnico. Nenhuma comunicação é enviada automaticamente.</p>
    </div>

    <div class="space-y-4">
        @forelse ($suggestions as $suggestion)
            <article class="rounded-md border border-ink-100 p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-ink-900">{{ str_replace('_', ' ', $suggestion->flag_code) }}</p>
                        <p class="text-xs text-ink-500">{{ $suggestion->severity->label() }} · {{ $suggestion->status->label() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('backoffice.document-ai.assistant.suggestions.accept', $suggestion) }}">
                            @csrf
                            <input type="hidden" name="confirm_accept" value="1">
                            <input type="text" name="accept_reason" value="{{ old('accept_reason') }}" class="mb-2 w-full rounded-md border-ink-200 text-xs" placeholder="Justificação técnica">
                            <button type="submit" class="mv-button-secondary">Aceitar</button>
                        </form>
                        <form method="POST" action="{{ route('backoffice.document-ai.assistant.suggestions.dismiss', $suggestion) }}">
                            @csrf
                            <input type="text" name="dismiss_reason" value="{{ old('dismiss_reason') }}" class="mb-2 w-full rounded-md border-ink-200 text-xs" placeholder="Justificação técnica">
                            <button type="submit" class="mv-button-secondary">Descartar</button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('backoffice.document-ai.assistant.suggestions.update', $suggestion) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <textarea name="suggestion" rows="4" class="w-full rounded-md border-ink-200 text-sm">{{ old('suggestion', $suggestion->suggestion) }}</textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="mv-button-primary">Guardar rascunho</button>
                    </div>
                </form>
            </article>
        @empty
            <p class="rounded-md border border-ink-100 px-4 py-6 text-center text-sm text-ink-500">Sem sugestões geradas para esta análise.</p>
        @endforelse
    </div>
</section>
