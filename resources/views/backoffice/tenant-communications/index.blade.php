<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Operações de inquilino"
            title="Comunicações de inquilino"
            description="Acompanhe comunicações submetidas pelos inquilinos."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($communications as $communication)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('backoffice.tenant-operations.communications.show', $communication) }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $communication->subject }} · {{ $communication->tenant?->name }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $communication->last_message_at?->format('d/m/Y H:i') }}</p>
                        </div>

                        <x-mv.badge>{{ $communication->status?->label() }}</x-mv.badge>
                    </div>
                </a>
            @empty
                <x-mv.alert>Sem comunicações de inquilino.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $communications->links() }}
    </div>
</x-app-layout>
