<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Documentação adicional</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $application->application_number ?? 'Candidatura em preparação' }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface p-6">
                <form method="POST" action="{{ route('candidate.additional-documents.store', $application) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                    <div>
                        <x-input-label for="additional_document_request_id" value="Pedido associado" />
                        <select id="additional_document_request_id" name="additional_document_request_id" class="mt-1 block w-full rounded-md border-ink-200">
                            <option value="">Documento adicional espontâneo</option>
                            @foreach ($application->additionalDocumentRequests as $request)
                                <option value="{{ $request->id }}">{{ $request->title }} @if($request->due_at) · prazo {{ $request->due_at->format('d/m/Y H:i') }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="title" value="Título" />
                        <x-text-input id="title" name="title" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="description" value="Descrição" />
                        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-ink-200"></textarea>
                    </div>
                    <div>
                        <x-input-label for="file" value="Ficheiro" />
                        <input id="file" name="file" type="file" required class="mt-1 block w-full text-sm text-ink-700">
                        <p class="mt-2 text-xs text-ink-500">PDF, JPG, PNG ou WebP até 10 MB. O ficheiro fica em storage privado.</p>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>
                    <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Submeter documento</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
