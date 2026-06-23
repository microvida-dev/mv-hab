@props(['registration'])

@php
    $household = $registration?->household;
    $incomeComplete = $household?->members?->isNotEmpty()
        && $household->members->every(fn ($member) => $member->has_no_income || $member->incomeRecords->isNotEmpty());
    $steps = [
        ['label' => 'Utilizador', 'route' => 'candidate.registration.show', 'active' => 'candidate.registration.*', 'complete' => $registration?->completionPercentage() === 100],
        ['label' => 'Agregado', 'route' => 'candidate.household.show', 'active' => 'candidate.household*', 'complete' => $household?->members?->isNotEmpty() ?? false],
        ['label' => 'Rendimentos', 'route' => 'candidate.income-records.index', 'active' => 'candidate.income-records.*', 'complete' => (bool) $incomeComplete],
        ['label' => 'Habitação atual', 'route' => 'candidate.current-housing.show', 'active' => 'candidate.current-housing.*', 'complete' => $registration?->currentHousingSituation !== null],
    ];
@endphp

<nav aria-label="Etapas do Registo de Adesão" class="overflow-x-auto border-b border-ink-100 bg-white">
    <ol class="mx-auto flex min-w-max max-w-7xl gap-1 px-4 sm:px-6 lg:px-8">
        @foreach ($steps as $step)
            <li>
                <a href="{{ route($step['route']) }}"
                   class="flex min-h-14 items-center gap-2 border-b-2 px-3 text-sm font-semibold {{ request()->routeIs($step['active']) ? 'border-civic-700 text-civic-900' : 'border-transparent text-ink-500 hover:text-ink-900' }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md {{ $step['complete'] ? 'bg-civic-50 text-civic-700' : 'bg-ink-100 text-ink-500' }}">
                        <x-ui-icon :name="$step['complete'] ? 'check' : 'arrow'" class="h-3.5 w-3.5" />
                    </span>
                    {{ $loop->iteration }}. {{ $step['label'] }}
                </a>
            </li>
        @endforeach
    </ol>
</nav>
