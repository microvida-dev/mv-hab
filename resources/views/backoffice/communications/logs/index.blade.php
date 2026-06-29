<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Comunicações</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Histórico e envios</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
        @can('create', \App\Models\CommunicationLog::class)
            <section class="rounded-2xl mv-surface p-6"><h2 class="text-lg font-semibold">Comunicação manual</h2><form method="POST" action="{{ route('backoffice.communications.logs.store') }}" class="mt-5 grid gap-4 md:grid-cols-2">@csrf
                <select name="recipient_user_id" class="mv-input" required><option value="">Destinatário</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>@endforeach</select>
                <select name="channel" class="mv-input">@foreach($channels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                <x-text-input name="event_code" value="manual.communication" placeholder="Código do evento" required />
                <select name="priority" class="mv-input"><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select>
                <x-text-input name="title" placeholder="Título" required />
                <x-text-input name="subject" placeholder="Assunto" />
                <textarea name="body" rows="5" class="mv-input md:col-span-2" placeholder="Conteúdo" required></textarea>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="requires_acknowledgement" value="1" class="mv-checkbox">Exige tomada de conhecimento</label>
                <div class="text-right"><x-primary-button>Criar comunicação</x-primary-button></div>
            </form></section>
        @endcan
        <div class="overflow-hidden rounded-2xl mv-surface"><table class="min-w-full divide-y divide-ink-100 text-sm"><thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Número</th><th class="px-4 py-3">Título</th><th class="px-4 py-3">Destinatário</th><th class="px-4 py-3">Evento</th><th class="px-4 py-3">Estado</th><th></th></tr></thead><tbody class="divide-y divide-ink-100">
            @forelse($communications as $communication)<tr><td class="px-4 py-3 font-mono text-xs">{{ $communication->communication_number }}</td><td class="px-4 py-3 font-semibold">{{ $communication->title }}</td><td class="px-4 py-3">{{ $communication->recipient?->name }}</td><td class="px-4 py-3">{{ $communication->event_code }}</td><td class="px-4 py-3">{{ $communication->status->label() }}</td><td class="px-4 py-3 text-right"><a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.communications.logs.show', $communication) }}">Abrir</a></td></tr>@empty<tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">Sem comunicações.</td></tr>@endforelse
        </tbody></table></div><div class="mt-4">{{ $communications->links() }}</div>
    </div></div>
</x-app-layout>
