<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Documentos"
            title="Checklist documental"
            description="Complete os documentos necessários para preparar futuras candidaturas."
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.documents.index') }}" class="mv-button-secondary">
                    Documentos submetidos
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section
                eyebrow="Progresso documental"
                title="Preparação documental"
                description="A validação final dependerá das regras do programa e do concurso a que se candidatar."
            >
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="mt-1 text-3xl font-semibold text-ink-900">
                            {{ $checklist['summary']['percentage'] }}%
                        </p>
                    </div>

                    <p class="max-w-xl text-sm leading-6 text-ink-600">
                        {{ $checklist['next_step'] }}
                    </p>
                </div>

                <div class="mt-4 h-2 overflow-hidden rounded bg-ink-100">
                    <div
                        class="h-full bg-mvhab-primary"
                        style="width: {{ $checklist['summary']['percentage'] }}%"
                    ></div>
                </div>

                <p class="mt-4 text-xs leading-5 text-ink-500">
                    A submissão de documentos nesta área prepara o seu processo para futuras candidaturas.
                </p>
            </x-mv.section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    'Obrigatórios' => $checklist['summary']['total_required'],
                    'Em falta' => $checklist['summary']['missing'],
                    'Submetidos' => $checklist['summary']['submitted'],
                    'Validados' => $checklist['summary']['validated'],
                    'Rejeitados' => $checklist['summary']['rejected'],
                ] as $label => $value)
                    <x-mv.stat-card
                        :label="$label"
                        :value="$value"
                    />
                @endforeach
            </section>

            @forelse ($checklist['groups'] as $group => $items)
                <section class="space-y-3">
                    <h2 class="text-base font-semibold text-ink-900">{{ $group }}</h2>
                    <div class="grid gap-3">
                        @foreach ($items as $item)
                           @php
                                $status = $item['status'];

                                $statusTone = match ($status) {
                                    \App\Enums\DocumentStatus::Validated => 'success',
                                    \App\Enums\DocumentStatus::Rejected,
                                    \App\Enums\DocumentStatus::Expired => 'danger',
                                    \App\Enums\DocumentStatus::Missing => 'warning',
                                    default => 'neutral',
                                };
                            @endphp
                            <article class="mv-surface p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-semibold text-ink-900">{{ $item['document_type']->name }}</h3>
                                            <x-mv.badge :tone="$statusTone">
                                                {{ $status->label() }}
                                            </x-mv.badge>
                                            @if ($item['is_required'])
                                                <x-mv.badge>
                                                    Obrigatório
                                                </x-mv.badge>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-ink-500">{{ $item['target_label'] }}</p>
                                        @if ($item['instructions'])
                                            <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $item['instructions'] }}</p>
                                        @endif
                                        @if ($item['submission']?->rejection_reason)
                                            <x-mv.alert tone="danger" class="mt-3 px-3 py-2">
                                                {{ $item['submission']->rejection_reason }}
                                            </x-mv.alert>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if ($item['submission'])
                                            <a href="{{ route('candidate.documents.show', $item['submission']) }}" class="mv-button-secondary">Detalhe</a>
                                            @can('replace', $item['submission'])
                                                <a href="{{ route('candidate.documents.replace.create', $item['submission']) }}" class="mv-button-primary">Substituir</a>
                                            @endcan
                                        @else
                                            <a href="{{ route('candidate.documents.create', [
                                                'item' => $item['key'],
                                                'required_document_id' => $item['required_document_id'],
                                                'target_type' => $item['target_type'],
                                                'target_id' => $item['target_id'],
                                            ]) }}" class="mv-button-primary">
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
                <x-mv.section
                    title="Não existem documentos configurados"
                    description="A equipa municipal ainda não configurou a matriz documental."
                />
            @endforelse
        </div>
    </div>
</x-app-layout>
