<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Comunicação"
            :title="$tenantCommunication->subject"
            description="Histórico de mensagens desta comunicação."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section title="Mensagens">
            <x-mv.badge>{{ $tenantCommunication->status?->label() }}</x-mv.badge>

            <div class="mt-5 space-y-4">
                @foreach ($tenantCommunication->messages as $message)
                    <div class="rounded-2xl border border-ink-100 p-4">
                        <p class="text-xs text-ink-500">
                            {{ $message->sender_type }} · {{ $message->created_at?->format('d/m/Y H:i') }}
                        </p>

                        <p class="mt-2 text-sm leading-6 text-ink-700">
                            {{ $message->body }}
                        </p>
                    </div>
                @endforeach
            </div>
        </x-mv.section>

        <form
            method="POST"
            action="{{ route('tenant.communications.messages.store', $tenantCommunication) }}"
            class="space-y-6"
        >
            @csrf

            <x-mv.section title="Responder">
                <x-ui.field label="Responder" for="body" name="body" required>
                    <x-ui.textarea id="body" name="body" rows="4" required />
                </x-ui.field>
            </x-mv.section>

            <div class="flex justify-end">
                <button class="mv-button-primary" type="submit">
                    Enviar resposta
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
