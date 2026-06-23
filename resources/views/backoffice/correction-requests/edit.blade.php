<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Editar {{ $correctionRequest->request_number }}</h1></x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('backoffice.correction-requests.update', $correctionRequest) }}" class="mv-surface space-y-4 p-6">
                @csrf
                @method('PATCH')
                <input name="subject" value="{{ old('subject', $correctionRequest->subject) }}" class="w-full rounded-md border-ink-300 text-sm">
                <textarea name="message" rows="4" class="w-full rounded-md border-ink-300 text-sm">{{ old('message', $correctionRequest->message) }}</textarea>
                <textarea name="instructions" rows="3" class="w-full rounded-md border-ink-300 text-sm">{{ old('instructions', $correctionRequest->instructions) }}</textarea>
                <button class="mv-button-primary">Atualizar</button>
            </form>
        </div>
    </div>
</x-app-layout>
