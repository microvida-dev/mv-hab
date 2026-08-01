<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Lote selado</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    {{ $batch->contest->title }} · lote {{ $batch->sequence_number }}
                </h1>
                <p class="mt-1 text-sm text-ink-500">
                    {{ $batch->cycle->label() }} · {{ $batch->sealed_at?->format('d/m/Y H:i') }}
                </p>
            </div>
            <a
                href="{{ $batch->correctionRequest
                    ? route('backoffice.correction-requests.show', $batch->correctionRequest)
                    : route('backoffice.application-review-batches.contest', $batch->contest) }}"
                class="mv-button-secondary"
            >
                {{ $batch->correctionRequest ? 'Voltar ao pedido' : 'Voltar ao concurso' }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 md:grid-cols-4">
                <x-mv.stat-card label="Estado" :value="$batch->status->label()" />
                <x-mv.stat-card label="Processos" :value="$batch->item_count" />
                <x-mv.stat-card label="Selado por" :value="$batch->sealedBy?->name ?? 'Utilizador removido'" />
                <x-mv.stat-card label="Referência" :value="substr($batch->public_id, 0, 12).'…'" />
            </section>

            @can('publishForBatch', [\App\Models\ApplicationReviewPublication::class, $batch])
                <div class="flex justify-end">
                    <a href="{{ route('backoffice.application-review-publications.create', $batch) }}" class="mv-button-primary">
                        {{ $batch->publication ? 'Ver publicação' : ($batch->correctionRequest ? 'Preparar publicação da segunda análise' : 'Preparar publicação coletiva') }}
                    </a>
                </div>
            @endcan

            <x-mv.alert tone="info" title="Integridade verificada por hash">
                <p class="break-all font-mono text-xs">{{ $batch->snapshot_hash }}</p>
            </x-mv.alert>

            <section class="mv-surface p-5">
                <h2 class="text-lg font-semibold text-ink-900">Fundamento</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-600">{{ $batch->reason }}</p>
            </section>

            <section class="space-y-3">
                @foreach ($batch->items as $item)
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $item->process_number }}</p>
                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $item->application_number ?: $item->application_public_id }}
                                </p>
                            </div>
                            <x-mv.badge :tone="match ($item->outcome->value) {
                                'correction_required' => 'warning',
                                'correction_rejected' => 'danger',
                                default => 'success',
                            }">
                                {{ $item->outcome->label() }}
                            </x-mv.badge>
                        </div>

                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                            <div><dt class="text-ink-400">Resultado técnico</dt><dd class="mt-1 font-semibold text-ink-800">{{ $item->technical_result ?? 'Não aplicável' }}</dd></div>
                            <div><dt class="text-ink-400">Documentos</dt><dd class="mt-1 font-semibold text-ink-800">{{ count($item->document_snapshot) }}</dd></div>
                            <div><dt class="text-ink-400">Versão da análise</dt><dd class="mt-1 font-semibold text-ink-800">{{ $item->review_lock_version ?? '—' }}</dd></div>
                            <div><dt class="text-ink-400">Hash</dt><dd class="mt-1 break-all font-mono text-xs text-ink-600">{{ $item->snapshot_hash }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
