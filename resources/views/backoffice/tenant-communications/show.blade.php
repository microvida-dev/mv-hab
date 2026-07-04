<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Comunicação de inquilino"
            :title="$tenantCommunication->subject"
            :description="$tenantCommunication->tenant?->name"
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section title="Mensagens">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm text-ink-500">{{ $tenantCommunication->tenant?->name }}</span>
                <x-mv.badge>{{ $tenantCommunication->status?->label() }}</x-mv.badge>
            </div>

            <div class="mt-5 space-y-4">
                @foreach ($tenantCommunication->messages as $message)
                    <div class="rounded-2xl border border-ink-100 p-4">
                        <p class="text-xs text-ink-500">{{ $message->sender_type }} · {{ $message->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mt-2 text-sm leading-6 text-ink-700">{{ $message->body }}</p>
                    </div>
                @endforeach
            </div>
        </x-mv.section>

        <form class="space-y-6" method="POST" action="{{ route('backoffice.tenant-operations.communications.messages.store', $tenantCommunication) }}">
            @csrf

            <x-mv.section title="Responder">
                <label class="grid gap-1 text-sm font-medium">
                    Responder
                    <textarea class="mv-input" name="body" rows="4" required></textarea>
                </label>
            </x-mv.section>

            <button class="mv-button-primary" type="submit">Registar mensagem</button>
        </form>
    </div>
</x-app-layout>
