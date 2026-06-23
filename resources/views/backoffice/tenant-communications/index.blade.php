<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Comunicações de inquilino</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        @forelse ($communications as $communication)
            <a class="mv-card block" href="{{ route('backoffice.tenant-operations.communications.show', $communication) }}">
                <p class="font-semibold">{{ $communication->subject }} · {{ $communication->tenant?->name }}</p>
                <p class="text-sm text-ink-500">{{ $communication->status?->label() }} · {{ $communication->last_message_at?->format('d/m/Y H:i') }}</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem comunicações de inquilino.</p></div>
        @endforelse
        {{ $communications->links() }}
    </div>
</x-app-layout>
