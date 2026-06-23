<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Pedido {{ $ticket->ticket_number }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $ticket->subject }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $ticket->category->label() }} · {{ $ticket->status->label() }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="space-y-4">
                @foreach ($messages as $message)
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="font-semibold text-ink-900">{{ $message->sender?->name ?? 'Sistema' }}</p>
                            <p class="text-xs text-ink-500">{{ $message->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $message->message }}</p>
                    </article>
                @endforeach
            </section>

            @if ($ticket->acceptsCandidateReply())
                <form method="POST" action="{{ route('candidate.support-ticket-messages.store', $ticket) }}" class="mv-surface space-y-4 p-6">
                    @csrf
                    <h2 class="text-lg font-semibold text-ink-900">Responder</h2>
                    <textarea name="message" rows="5" class="w-full rounded-md border-ink-300 text-sm" required>{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    <div class="flex justify-end"><button type="submit" class="mv-button-primary">Enviar resposta</button></div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
