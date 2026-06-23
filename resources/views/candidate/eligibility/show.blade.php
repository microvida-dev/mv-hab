@php
    $resultClasses = match ($check->result) {
        \App\Enums\EligibilityResult::Eligible => 'bg-civic-50 text-civic-900',
        \App\Enums\EligibilityResult::Ineligible => 'bg-red-50 text-red-800',
        \App\Enums\EligibilityResult::RequiresReview => 'bg-signal-50 text-signal-700',
        default => 'bg-ink-100 text-ink-700',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Resultado indicativo</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $check->contest?->title ?? $check->program?->name ?? 'Elegibilidade' }}</h1>
                <p class="mt-1 text-sm text-ink-500">Executado em {{ $check->executed_at?->format('d/m/Y H:i') }}</p>
            </div>
            <span class="rounded-md px-3 py-1.5 text-sm font-semibold {{ $resultClasses }}">{{ $check->result->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="border-l-4 border-civic-700 bg-civic-50 p-5 text-sm leading-6 text-civic-900">
                Esta verificação é indicativa e baseia-se nos dados atualmente declarados. A decisão final depende da análise dos serviços municipais e das regras do programa ou concurso.
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-xl font-semibold text-ink-900">{{ $check->result->label() }}</h2>
                <p class="mt-3 text-sm leading-6 text-ink-600">{{ $check->summary }}</p>
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="border-b border-ink-100 px-6 py-4">
                    <h2 class="font-semibold text-ink-900">Condições verificadas</h2>
                </div>
                <div class="divide-y divide-ink-100">
                    @forelse ($check->results as $result)
                        <div class="flex gap-4 px-6 py-5">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md {{ $result->result === \App\Enums\EligibilityCriterionResult::Passed ? 'bg-civic-50 text-civic-700' : ($result->result === \App\Enums\EligibilityCriterionResult::Failed ? 'bg-red-50 text-red-700' : 'bg-signal-50 text-signal-700') }}">
                                <x-ui-icon :name="$result->result === \App\Enums\EligibilityCriterionResult::Passed ? 'check' : 'alert'" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="font-semibold text-ink-900">{{ $result->name }}</p>
                                <p class="mt-1 text-sm leading-6 text-ink-600">{{ $result->message }}</p>
                                <p class="mt-1 text-xs font-semibold text-ink-500">{{ $result->result->label() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-5 text-sm text-ink-500">Não existiam critérios aplicáveis.</p>
                    @endforelse
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="font-semibold text-ink-900">Atualizar informação</h2>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('candidate.registration.show') }}" class="mv-button-secondary">Registo de Adesão</a>
                    <a href="{{ route('candidate.household.show') }}" class="mv-button-secondary">Agregado</a>
                    <a href="{{ route('candidate.income-records.index') }}" class="mv-button-secondary">Rendimentos</a>
                    <a href="{{ route('candidate.current-housing.show') }}" class="mv-button-secondary">Habitação atual</a>
                    <a href="{{ route('candidate.documents.checklist') }}" class="mv-button-secondary">Documentos</a>
                    <a href="{{ route('public.contests.index') }}" class="mv-button-secondary">Concursos</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
