<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Responder a aperfeiçoamento</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface p-6">
            <p class="text-sm text-ink-600">{{ $correctionRequest->subject }}</p>
            <form method="POST" action="{{ route('candidate.advanced-correction-requests.respond.store', [$application, $correctionRequest]) }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="correction_request_id" value="{{ $correctionRequest->id }}">
                <select name="correction_request_item_id" required class="block w-full rounded-md border-ink-200">
                    @foreach ($correctionRequest->items as $item)
                        <option value="{{ $item->id }}">{{ $item->title }}</option>
                    @endforeach
                </select>
                <textarea name="message" rows="7" required class="block w-full rounded-md border-ink-200"></textarea>
                <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Submeter resposta</button>
            </form>
        </section>
    </div></div>
</x-app-layout>
