<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Comunicações</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div><a class="mv-button-primary" href="{{ route('tenant.communications.create') }}">Nova comunicação</a></div>
        @forelse ($communications as $communication)
            <a class="mv-card block" href="{{ route('tenant.communications.show', $communication) }}">
                <p class="font-semibold">{{ $communication->subject }}</p>
                <p class="text-sm text-ink-500">{{ $communication->status?->label() }} · {{ $communication->messages_count ?? $communication->messages->count() }} mensagens</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem comunicações abertas.</p></div>
        @endforelse
        {{ $communications->links() }}
    </div>
</x-app-layout>
