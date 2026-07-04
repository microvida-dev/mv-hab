<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            :eyebrow="'Ticket '.$ticket->ticket_number"
            :title="$ticket->subject"
            :description="$ticket->user?->name"
        >
            <x-slot name="actions">
                <x-mv.badge>{{ $ticket->status->label() }}</x-mv.badge>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="grid gap-4 lg:grid-cols-[1fr_20rem]">
                <div class="space-y-4">
                    @foreach ($ticket->messages as $message)
                        <x-mv.section>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold text-ink-900">{{ $message->sender?->name ?? 'Sistema' }}</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-mv.badge>{{ $message->visibility->label() }}</x-mv.badge>
                                    <p class="text-xs text-ink-500">{{ $message->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $message->message }}</p>
                        </x-mv.section>
                    @endforeach

                    <form method="POST" action="{{ route('backoffice.support-ticket-messages.store', $ticket) }}">
                        @csrf
                        <x-mv.section title="Nova mensagem">
                            <select name="visibility" class="mv-input w-full text-sm">
                                <option value="candidate_visible">Visível ao candidato</option>
                                <option value="internal_only">Apenas interno</option>
                            </select>
                            <textarea name="message" rows="5" class="mv-input mt-4 w-full text-sm" required></textarea>
                            <div class="mt-4">
                                <button type="submit" class="mv-button-primary">Registar mensagem</button>
                            </div>
                        </x-mv.section>
                    </form>
                </div>

                <aside class="space-y-4">
                    <form method="POST" action="{{ route('backoffice.support-tickets.assign', $ticket) }}">
                        @csrf
                        <x-mv.section title="Atribuição" padding="p-5">
                            <select name="assigned_to" class="mv-input w-full text-sm" required>
                                @foreach ($staffUsers as $staff)
                                    <option value="{{ $staff->id }}" @selected($ticket->assigned_to === $staff->id)>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                            <button class="mv-button-secondary mt-3 w-full">Atribuir</button>
                        </x-mv.section>
                    </form>
                    <form method="POST" action="{{ route('backoffice.support-tickets.status', $ticket) }}">
                        @csrf
                        <x-mv.section title="Estado" padding="p-5">
                            <select name="status" class="mv-input w-full text-sm" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->status->value === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <textarea name="message" rows="3" class="mv-input mt-3 w-full text-sm" placeholder="Nota opcional"></textarea>
                            <button class="mv-button-secondary mt-3 w-full">Atualizar</button>
                        </x-mv.section>
                    </form>
                    @if ($ticket->attachments->isNotEmpty())
                        <x-mv.section title="Anexos" padding="p-5">
                            <div class="mt-3 space-y-2">
                                @foreach ($ticket->attachments as $attachment)
                                    <a href="{{ route('backoffice.support-ticket-attachments.download', $attachment) }}" class="block text-sm font-semibold text-mvhab-primary">{{ $attachment->original_filename }}</a>
                                @endforeach
                            </div>
                        </x-mv.section>
                    @endif
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
