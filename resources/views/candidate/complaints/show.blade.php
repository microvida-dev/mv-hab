<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamação"
            :title="$complaint->complaint_number"
            :description="$complaint->subject"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Detalhe da reclamação">
                <x-mv.badge>{{ $complaint->status->label() }}</x-mv.badge>

                <p class="mt-5 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $complaint->grounds }}</p>
            </x-mv.section>

            @if ($complaint->status->value === 'draft')
                <form method="POST" action="{{ route('candidate.complaints.submit', $complaint) }}" class="space-y-4">
                    @csrf

                    <x-mv.checkbox-card
                        name="truthfulness_confirmed"
                        label="Confirmo a veracidade dos elementos apresentados."
                        required
                        align="start"
                    />

                    <button class="mv-button-primary">Submeter reclamação</button>
                </form>
            @endif

            @if ($complaint->decision && $complaint->decision->candidate_visible)
                <x-mv.section title="Decisão">
                    <p class="text-sm leading-6 text-ink-700">{{ $complaint->decision->summary }}</p>
                </x-mv.section>
            @endif
        </div>
    </div>
</x-app-layout>
