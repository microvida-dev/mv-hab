<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Documentos</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Substituir documento</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $submission->documentType->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.documents.replace.store', $submission) }}" enctype="multipart/form-data" class="mv-surface space-y-6 p-6">
                @csrf

                <div class="rounded-md bg-signal-50 p-4 text-sm leading-6 text-signal-900">
                    Ao substituir este documento, a versão anterior será mantida no histórico do processo e a nova versão ficará pendente de análise.
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="issue_date" value="Data de emissão" />
                        <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', optional($submission->issue_date)->format('Y-m-d'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('issue_date')" />
                    </div>
                    <div>
                        <x-input-label for="expiry_date" value="Data de validade" />
                        <x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date', optional($submission->expiry_date)->format('Y-m-d'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('expiry_date')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="file" value="Novo ficheiro" />
                    <input id="file" name="file" type="file" class="mt-1 block w-full rounded-md border border-ink-200 px-3 py-2 text-sm" required>
                    <p class="mt-2 text-xs text-ink-500">Formatos permitidos: PDF, JPG, PNG ou WEBP. Tamanho máximo: {{ $submission->documentType->max_file_size_mb }} MB.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('file')" />
                </div>

                <div>
                    <x-input-label for="notes" value="Notas opcionais" />
                    <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-ink-300 shadow-sm focus:border-civic-700 focus:ring-civic-700">{{ old('notes') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.documents.show', $submission) }}" class="mv-button-secondary">Cancelar</a>
                    <button type="submit" class="mv-button-primary">
                        <x-ui-icon name="document" class="h-4 w-4" />
                        Substituir documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
