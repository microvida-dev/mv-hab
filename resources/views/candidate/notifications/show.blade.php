<x-app-layout>
    <x-slot name="header"><div><p class="font-mono text-xs text-civic-700">{{ $officialNotification->notification_number }}</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $officialNotification->title ?: $officialNotification->subject }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <article class="rounded-md border border-ink-100 bg-white p-7">
            <div class="flex flex-wrap justify-between gap-3 text-sm text-ink-500"><span>{{ $officialNotification->status->label() }} · {{ $officialNotification->channel->label() }}</span><span>{{ $officialNotification->created_at->format('d/m/Y H:i') }}</span></div>
            <div class="mt-6 whitespace-pre-line text-sm leading-7 text-ink-800">{{ $officialNotification->body }}</div>
            @if($officialNotification->action_url)<a href="{{ $officialNotification->action_url }}" class="mv-button-primary mt-6">Abrir ação associada</a>@endif
        </article>
        <div class="flex flex-wrap gap-3">
            @if(!$officialNotification->read_at)<form method="POST" action="{{ route('candidate.notifications.mark-read', $officialNotification) }}">@csrf<x-secondary-button>Marcar como lida</x-secondary-button></form>@endif
            @if($officialNotification->requires_acknowledgement && !$officialNotification->acknowledged_at)<form method="POST" action="{{ route('candidate.notifications.acknowledge', $officialNotification) }}">@csrf<x-primary-button>Tomar conhecimento</x-primary-button></form>@endif
            @if(!$officialNotification->archived_at)<form method="POST" action="{{ route('candidate.notifications.archive', $officialNotification) }}">@csrf<x-secondary-button>Arquivar</x-secondary-button></form>@endif
        </div>
        @if($officialNotification->acknowledged_at)<p class="text-sm text-civic-800">Tomada de conhecimento registada em {{ $officialNotification->acknowledged_at->format('d/m/Y H:i') }}.</p>@endif
    </div></div>
</x-app-layout>
