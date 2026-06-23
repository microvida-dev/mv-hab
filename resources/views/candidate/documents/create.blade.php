<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Documentos</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Submeter documento</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $item['document_type']->name }} · {{ $item['target_label'] }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <form method="POST" action="{{ route('candidate.documents.store') }}" enctype="multipart/form-data" class="mv-surface space-y-6 p-6">
                @csrf
                <input type="hidden" name="document_type_id" value="{{ $item['document_type_id'] }}">
                <input type="hidden" name="required_document_id" value="{{ $item['required_document_id'] }}">
                @if ($application)
                    <input type="hidden" name="application_public_id" value="{{ $application->public_id }}">
                @endif
                @if ($item['target_type'] === 'household_member')
                    <input type="hidden" name="household_member_id" value="{{ $item['target_id'] }}">
                @elseif ($item['target_type'] === 'income_record')
                    <input type="hidden" name="income_record_id" value="{{ $item['target_id'] }}">
                @elseif ($item['target_type'] === 'current_housing_situation')
                    <input type="hidden" name="current_housing_situation_id" value="{{ $item['target_id'] }}">
                @endif

                <div class="rounded-md bg-civic-50 p-4 text-sm leading-6 text-civic-900">
                    Submeta apenas documentos legíveis e correspondentes ao tipo solicitado. Documentos ilegíveis, incompletos ou incorretos poderão ser rejeitados pelos serviços.
                </div>

                <div>
                    <x-input-label for="title" value="Título opcional" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="issue_date" value="Data de emissão" />
                        <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date')" />
                        <x-input-error class="mt-2" :messages="$errors->get('issue_date')" />
                    </div>
                    <div>
                        <x-input-label for="expiry_date" value="Data de validade" />
                        <x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" />
                        <x-input-error class="mt-2" :messages="$errors->get('expiry_date')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="file" value="Ficheiro" />
                    <input id="file" name="file" type="file" class="mt-1 block w-full rounded-md border border-ink-200 px-3 py-2 text-sm" required>
                    <p class="mt-2 text-xs text-ink-500">Formatos permitidos: PDF, JPG, PNG ou WEBP. Tamanho máximo: {{ $item['document_type']->max_file_size_mb }} MB.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('file')" />
                </div>

                <div>
                    <x-input-label for="notes" value="Notas opcionais" />
                    <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-ink-300 shadow-sm focus:border-civic-700 focus:ring-civic-700">{{ old('notes') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ $application ? route('candidate.applications.review', $application) : route('candidate.documents.checklist') }}" class="mv-button-secondary">Cancelar</a>
                    <button type="submit" class="mv-button-primary">
                        <x-ui-icon name="document" class="h-4 w-4" />
                        Guardar documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
