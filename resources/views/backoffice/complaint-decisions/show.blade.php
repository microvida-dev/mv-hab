<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Decisão"
            :title="$complaintDecision->decision_number"
            description="Consulta e aprovação formal da decisão administrativa associada à reclamação."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Decisão administrativa">
                <div class="flex flex-wrap gap-2 text-sm text-ink-500">
                    <x-mv.badge>{{ $complaintDecision->status->label() }}</x-mv.badge>
                    <x-mv.badge>{{ $complaintDecision->decision_result->label() }}</x-mv.badge>
                </div>

                <h2 class="mt-4 font-semibold">Resumo</h2>
                <p class="mt-2 whitespace-pre-line text-sm">{{ $complaintDecision->summary }}</p>

                <h2 class="mt-4 font-semibold">Fundamentos</h2>
                <p class="mt-2 whitespace-pre-line text-sm">{{ $complaintDecision->grounds }}</p>
            </x-mv.section>

            <form method="POST" action="{{ route('backoffice.complaint-decisions.approve', $complaintDecision) }}">
                @csrf
                <button type="submit" class="mv-button-primary">Aprovar decisão</button>
            </form>
        </div>
    </div>
</x-app-layout>
