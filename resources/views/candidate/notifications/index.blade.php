<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-sm font-semibold text-civic-700">Área pessoal</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Notificações</h1></div>
            <p class="text-sm text-ink-600">{{ $unreadCount }} por ler</p>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
        <section class="grid gap-3 sm:grid-cols-5">
            @foreach (['unread' => 'Não lidas', 'read' => 'Lidas', 'action_required' => 'Ação obrigatória', 'expired' => 'Expiradas', 'archived' => 'Arquivadas'] as $key => $label)
                <div class="mv-surface p-4">
                    <p class="text-xs font-semibold uppercase text-ink-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-900">{{ $counts[$key] ?? 0 }}</p>
                </div>
            @endforeach
        </section>
        @forelse($notifications as $notification)
            @php($centerStatus = $center->centerStatusFor($notification))
            <section class="rounded-md border border-ink-100 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-ink-900">{{ $notification->title ?: $notification->subject }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ $notification->event_code ?: $notification->notification_type->label() }}</p>
                        @if ($notification->action_url)
                            <a href="{{ $notification->action_url }}" class="mt-3 inline-flex text-sm font-semibold text-civic-700">Abrir ação</a>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold {{ $centerStatus->value === 'unread' || $centerStatus->value === 'action_required' ? 'text-civic-700' : 'text-ink-500' }}">{{ $centerStatus->label() }}</p>
                        <p class="mt-1 text-xs text-ink-500">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('candidate.notifications.show', $notification) }}" class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold text-ink-700">Ver detalhe</a>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('candidate.notifications.mark-read', $notification) }}">
                            @csrf
                            <button class="rounded-md border border-civic-200 px-3 py-2 text-sm font-semibold text-civic-700">Marcar como lida</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('candidate.notifications.archive', $notification) }}">
                        @csrf
                        <button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold text-ink-700">Arquivar</button>
                    </form>
                </div>
            </section>
        @empty
            <div class="rounded-md border border-ink-100 bg-white p-10 text-center text-sm text-ink-500">Sem notificações.</div>
        @endforelse
        {{ $notifications->links() }}
    </div></div>
</x-app-layout>
