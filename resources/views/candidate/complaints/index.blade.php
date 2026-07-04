<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamações"
            title="As minhas reclamações"
            description="Acompanhe reclamações apresentadas no âmbito das listas e decisões do concurso."
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.complaints.create') }}" class="mv-button-primary">Nova reclamação</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section padding="p-0" class="overflow-hidden">
                @forelse ($complaints as $complaint)
                    <a href="{{ route('candidate.complaints.show', $complaint) }}" class="block border-b border-ink-100 p-4 transition hover:bg-ink-50">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $complaint->complaint_number }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $complaint->subject }}</p>
                            </div>

                            <x-mv.badge>{{ $complaint->status->label() }}</x-mv.badge>
                        </div>
                    </a>
                @empty
                    <x-mv.alert>Sem reclamações.</x-mv.alert>
                @endforelse
            </x-mv.section>

            {{ $complaints->links() }}
        </div>
    </div>
</x-app-layout>
