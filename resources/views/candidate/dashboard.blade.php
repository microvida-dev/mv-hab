@php
    $statusClasses = match ($registration?->status) {
        \App\Enums\AdhesionRegistrationStatus::Registered => 'bg-civic-50 text-civic-900',
        \App\Enums\AdhesionRegistrationStatus::Cancelled,
        \App\Enums\AdhesionRegistrationStatus::Removed,
        \App\Enums\AdhesionRegistrationStatus::Expired => 'bg-ink-100 text-ink-700',
        \App\Enums\AdhesionRegistrationStatus::Blocked => 'bg-red-50 text-red-800',
        default => 'bg-signal-50 text-signal-700',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Espaço reservado</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Área do Candidato</h1>
                <p class="mt-1 text-sm text-ink-500">Complete os dados preparatórios do seu Registo de Adesão.</p>
            </div>
            @if ($registration)
                <span class="rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                    {{ $registration->status->label() }}
                </span>
            @endif
        </div>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if (! $registration)
                <section class="mv-surface p-6">
                    <h2 class="text-xl font-semibold text-ink-900">Inicie o seu Registo de Adesão</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">O registo reúne os dados pessoais, do agregado, dos rendimentos e da situação habitacional necessários para futuras candidaturas.</p>
                    <a href="{{ route('candidate.registration.create') }}" class="mv-button-primary mt-5">
                        <x-ui-icon name="plus" class="h-4 w-4" />
                        Iniciar Registo de Adesão
                    </a>
                </section>
            @else
                <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="mv-surface p-6">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-civic-700">Progresso geral</p>
                                <p class="mt-1 text-3xl font-semibold text-ink-900">{{ $progress['overall'] }}%</p>
                            </div>
                            <p class="max-w-sm text-right text-sm text-ink-500">{{ $progress['next_step'] }}</p>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded bg-ink-100">
                            <div class="h-full bg-civic-700" style="width: {{ $progress['overall'] }}%"></div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach ($progress['sections'] as $section)
                                <a href="{{ route($section['route']) }}" class="rounded-md border border-ink-100 p-4 transition hover:bg-ink-50">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="font-semibold text-ink-900">{{ $section['label'] }}</span>
                                        <span class="text-sm font-semibold text-civic-700">{{ $section['percentage'] }}%</span>
                                    </div>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded bg-ink-100">
                                        <div class="h-full bg-civic-700" style="width: {{ $section['percentage'] }}%"></div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <aside class="mv-surface p-5">
                        <h2 class="font-semibold text-ink-900">Campos em falta</h2>
                        @if ($progress['missing'])
                            <ul class="mt-4 space-y-3 text-sm text-ink-600">
                                @foreach ($progress['missing'] as $missing)
                                    <li class="flex gap-2">
                                        <x-ui-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-signal-700" />
                                        <span>{{ $missing }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-4 text-sm leading-6 text-ink-600">Os dados preparatórios estão completos.</p>
                        @endif
                    </aside>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-ink-900">Resumo declarado</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="mv-surface p-5">
                            <p class="text-sm text-ink-500">Membros</p>
                            <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $progress['totals']['members'] }}</p>
                        </div>
                        <div class="mv-surface p-5">
                            <p class="text-sm text-ink-500">Rendimento mensal</p>
                            <p class="mt-2 text-2xl font-semibold text-ink-900">{{ number_format($progress['totals']['monthly'], 2, ',', '.') }} €</p>
                        </div>
                        <div class="mv-surface p-5">
                            <p class="text-sm text-ink-500">Rendimento anual</p>
                            <p class="mt-2 text-2xl font-semibold text-ink-900">{{ number_format($progress['totals']['annual'], 2, ',', '.') }} €</p>
                        </div>
                        <div class="mv-surface p-5">
                            <p class="text-sm text-ink-500">Habitação atual</p>
                            <p class="mt-2 text-base font-semibold text-ink-900">{{ $progress['housing_summary'] ?: 'Por preencher' }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-ink-500">Os valores apresentados resultam dos dados declarados e servem apenas para preparação do registo. A elegibilidade será avaliada posteriormente de acordo com as regras do programa e do concurso.</p>
                </section>

                @if ($documentProgress)
                    <section class="mv-surface p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-civic-700">Resumo documental</p>
                                <h2 class="mt-1 text-xl font-semibold text-ink-900">{{ $documentProgress['percentage'] }}% concluído</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">
                                    A submissão de documentos nesta área prepara o seu processo para futuras candidaturas. A validação final dependerá das regras do programa e do concurso a que se candidatar.
                                </p>
                            </div>
                            <a href="{{ route('candidate.documents.checklist') }}" class="mv-button-primary">
                                <x-ui-icon name="document" class="h-4 w-4" />
                                Checklist documental
                            </a>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-md border border-ink-100 p-4">
                                <p class="text-xs font-semibold uppercase text-ink-500">Obrigatórios</p>
                                <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $documentProgress['total_required'] }}</p>
                            </div>
                            <div class="rounded-md border border-ink-100 p-4">
                                <p class="text-xs font-semibold uppercase text-ink-500">Em falta</p>
                                <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $documentProgress['missing'] }}</p>
                            </div>
                            <div class="rounded-md border border-ink-100 p-4">
                                <p class="text-xs font-semibold uppercase text-ink-500">Submetidos</p>
                                <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $documentProgress['submitted'] }}</p>
                            </div>
                            <div class="rounded-md border border-ink-100 p-4">
                                <p class="text-xs font-semibold uppercase text-ink-500">Validados</p>
                                <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $documentProgress['validated'] }}</p>
                            </div>
                        </div>
                    </section>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
