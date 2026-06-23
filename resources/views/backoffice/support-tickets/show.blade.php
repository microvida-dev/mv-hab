<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Ticket {{ $ticket->ticket_number }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $ticket->subject }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $ticket->user?->name }} · {{ $ticket->status->label() }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="grid gap-4 lg:grid-cols-[1fr_20rem]">
                <div class="space-y-4">
                    @foreach ($ticket->messages as $message)
                        <article class="mv-surface p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold text-ink-900">{{ $message->sender?->name ?? 'Sistema' }}</p>
                                <p class="text-xs text-ink-500">{{ $message->visibility->label() }} · {{ $message->created_at?->format('d/m/Y H:i') }}</p>
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $message->message }}</p>
                        </article>
                    @endforeach

                    <form method="POST" action="{{ route('backoffice.support-ticket-messages.store', $ticket) }}" class="mv-surface space-y-4 p-6">
                        @csrf
                        <h2 class="text-lg font-semibold text-ink-900">Nova mensagem</h2>
                        <select name="visibility" class="w-full rounded-md border-ink-300 text-sm">
                            <option value="candidate_visible">Visível ao candidato</option>
                            <option value="internal_only">Apenas interno</option>
                        </select>
                        <textarea name="message" rows="5" class="w-full rounded-md border-ink-300 text-sm" required></textarea>
                        <button type="submit" class="mv-button-primary">Registar mensagem</button>
                    </form>
                </div>

                <aside class="space-y-4">
                    <form method="POST" action="{{ route('backoffice.support-tickets.assign', $ticket) }}" class="mv-surface space-y-3 p-5">
                        @csrf
                        <h2 class="font-semibold text-ink-900">Atribuição</h2>
                        <select name="assigned_to" class="w-full rounded-md border-ink-300 text-sm" required>
                            @foreach ($staffUsers as $staff)
                                <option value="{{ $staff->id }}" @selected($ticket->assigned_to === $staff->id)>{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        <button class="mv-button-secondary w-full">Atribuir</button>
                    </form>
                    <form method="POST" action="{{ route('backoffice.support-tickets.status', $ticket) }}" class="mv-surface space-y-3 p-5">
                        @csrf
                        <h2 class="font-semibold text-ink-900">Estado</h2>
                        <select name="status" class="w-full rounded-md border-ink-300 text-sm" required>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($ticket->status->value === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="message" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Nota opcional"></textarea>
                        <button class="mv-button-secondary w-full">Atualizar</button>
                    </form>
                    @if ($ticket->attachments->isNotEmpty())
                        <section class="mv-surface p-5">
                            <h2 class="font-semibold text-ink-900">Anexos</h2>
                            <div class="mt-3 space-y-2">
                                @foreach ($ticket->attachments as $attachment)
                                    <a href="{{ route('backoffice.support-ticket-attachments.download', $attachment) }}" class="block text-sm font-semibold text-civic-700">{{ $attachment->original_filename }}</a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
