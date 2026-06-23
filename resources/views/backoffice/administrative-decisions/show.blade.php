<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Decisão administrativa</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $decision->decision_type->label() }}</h1>
            </div>
            <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $decision->status->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <section class="mv-surface p-6">
            <p class="font-semibold text-ink-900">{{ $decision->decision_result->label() }}</p>
            <p class="mt-4 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $decision->summary }}</p>
            <p class="mt-4 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $decision->grounds }}</p>
        </section>
        @if ($decision->status !== \App\Enums\AdministrativeDecisionStatus::Approved)
            <form method="POST" action="{{ route('backoffice.administrative-decisions.approve', $decision) }}" class="mv-surface space-y-3 p-6">
                @csrf
                <label class="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="confirm_decision" value="1" class="rounded border-ink-300">Confirmo a aprovação desta decisão administrativa.</label>
                <button class="mv-button-primary">Aprovar decisão</button>
            </form>
        @endif
    </div></div>
</x-app-layout>
