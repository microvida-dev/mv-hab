<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Decisão administrativa"
            :title="$decision->decision_type->label()"
            description="Consulta, confirmação e aprovação formal da decisão administrativa."
        >
            <x-slot name="actions">
                <x-mv.badge>{{ $decision->status->label() }}</x-mv.badge>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section title="Conteúdo da decisão">
                <p class="font-semibold text-ink-900">{{ $decision->decision_result->label() }}</p>
                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $decision->summary }}</p>
                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $decision->grounds }}</p>
            </x-mv.section>

            @if ($decision->status !== \App\Enums\AdministrativeDecisionStatus::Approved)
                <form method="POST" action="{{ route('backoffice.administrative-decisions.approve', $decision) }}">
                    @csrf
                    <x-mv.section
                        title="Aprovação da decisão"
                        description="A aprovação aplica a decisão ao processo administrativo e fica registada em auditoria."
                    >
                        <x-mv.checkbox-card
                            name="confirm_decision"
                            label="Confirmo a aprovação desta decisão administrativa."
                            required
                        />
                        <div class="mt-4">
                            <button type="submit" class="mv-button-primary">Aprovar decisão</button>
                        </div>
                    </x-mv.section>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
