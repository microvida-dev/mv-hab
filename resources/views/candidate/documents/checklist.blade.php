<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Documentos</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Checklist documental</h1>
                <p class="mt-1 text-sm text-ink-500">Complete os documentos necessários para preparar futuras candidaturas.</p>
            </div>
            <a href="{{ route('candidate.documents.index') }}" class="mv-button-secondary">
                Documentos submetidos
            </a>
        </div>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface p-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-civic-700">Progresso documental</p>
                        <p class="mt-1 text-3xl font-semibold text-ink-900">{{ $checklist['summary']['percentage'] }}%</p>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-ink-600">{{ $checklist['next_step'] }}</p>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded bg-ink-100">
                    <div class="h-full bg-civic-700" style="width: {{ $checklist['summary']['percentage'] }}%"></div>
                </div>
                <p class="mt-4 text-xs leading-5 text-ink-500">A submissão de documentos nesta área prepara o seu processo para futuras candidaturas. A validação final dependerá das regras do programa e do concurso a que se candidatar.</p>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    'Obrigatórios' => $checklist['summary']['total_required'],
                    'Em falta' => $checklist['summary']['missing'],
                    'Submetidos' => $checklist['summary']['submitted'],
                    'Validados' => $checklist['summary']['validated'],
                    'Rejeitados' => $checklist['summary']['rejected'],
                ] as $label => $value)
                    <div class="mv-surface p-5">
                        <p class="text-sm text-ink-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            @forelse ($checklist['groups'] as $group => $items)
                <section class="space-y-3">
                    <h2 class="text-base font-semibold text-ink-900">{{ $group }}</h2>
                    <div class="grid gap-3">
                        @foreach ($items as $item)
                            @php
                                $status = $item['status'];
                                $statusClass = match ($status) {
                                    \App\Enums\DocumentStatus::Validated => 'bg-civic-50 text-civic-900',
                                    \App\Enums\DocumentStatus::Rejected, \App\Enums\DocumentStatus::Expired => 'bg-red-50 text-red-800',
                                    \App\Enums\DocumentStatus::Missing => 'bg-signal-50 text-signal-800',
                                    default => 'bg-ink-100 text-ink-700',
                                };
                            @endphp
                            <article class="mv-surface p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-semibold text-ink-900">{{ $item['document_type']->name }}</h3>
                                            <span class="rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status->label() }}</span>
                                            @if ($item['is_required'])
                                                <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">Obrigatório</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-ink-500">{{ $item['target_label'] }}</p>
                                        @if ($item['instructions'])
                                            <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $item['instructions'] }}</p>
                                        @endif
                                        @if ($item['submission']?->rejection_reason)
                                            <p class="mt-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-800">{{ $item['submission']->rejection_reason }}</p>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if ($item['submission'])
                                            <a href="{{ route('candidate.documents.show', $item['submission']) }}" class="mv-button-secondary">Detalhe</a>
                                            @can('replace', $item['submission'])
                                                <a href="{{ route('candidate.documents.replace.create', $item['submission']) }}" class="mv-button-primary">Substituir</a>
                                            @endcan
                                        @else
                                            <a href="{{ route('candidate.documents.create', ['item' => $item['key']]) }}" class="mv-button-primary">
                                                <x-ui-icon name="plus" class="h-4 w-4" />
                                                Submeter
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Não existem documentos configurados.</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">A equipa municipal ainda não configurou a matriz documental.</p>
                </section>
            @endforelse
        </div>
    </div>
</x-app-layout>
