<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiências"
            title="Audiência de interessados"
            description="Consulte os prazos e pronúncias disponíveis no âmbito do seu processo."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section padding="p-0" class="overflow-hidden">
                @forelse ($hearings as $hearing)
                    <a href="{{ route('candidate.hearings.show', $hearing) }}" class="block border-b border-ink-100 p-4 transition hover:bg-ink-50">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $hearing->subject }}</p>
                                <p class="mt-1 text-sm text-ink-500">Prazo {{ $hearing->deadline_at->format('d/m/Y H:i') }}</p>
                            </div>

                            <x-mv.badge>{{ $hearing->status->label() }}</x-mv.badge>
                        </div>
                    </a>
                @empty
                    <x-mv.alert>Sem audiências disponíveis.</x-mv.alert>
                @endforelse
            </x-mv.section>

            {{ $hearings->links() }}
        </div>
    </div>
</x-app-layout>
