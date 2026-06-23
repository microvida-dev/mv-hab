<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $tenantCommunication->subject }}</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card">
            <p class="text-sm text-ink-500">{{ $tenantCommunication->status?->label() }}</p>
            @foreach ($tenantCommunication->messages as $message)
                <div class="mt-4 rounded-md border border-ink-100 p-4">
                    <p class="text-xs text-ink-500">{{ $message->sender_type }} · {{ $message->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="mt-2 text-sm text-ink-700">{{ $message->body }}</p>
                </div>
            @endforeach
        </div>
        <form class="mv-card grid gap-4" method="POST" action="{{ route('tenant.communications.messages.store', $tenantCommunication) }}">
            @csrf
            <label class="grid gap-1 text-sm font-medium">Responder <textarea class="mv-input" name="body" rows="4" required></textarea></label>
            <button class="mv-button-primary" type="submit">Enviar resposta</button>
        </form>
    </div>
</x-app-layout>
