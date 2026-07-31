<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Fecho coletivo</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    {{ $contest->title }}
                </h1>
                <p class="mt-1 text-sm text-ink-500">
                    O lote abrange obrigatoriamente todos os processos do concurso e não publica resultados.
                </p>
            </div>
            <a
                href="{{ route('backoffice.application-review-batches.index') }}"
                class="mv-button-secondary"
            >
                Alterar concurso
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if ($errors->any())
                <x-mv.alert tone="danger" title="Não foi possível preparar o lote">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mv.alert>
            @endif

            @if ($inspection['blockers'] !== [])
                <x-mv.alert tone="warning" title="Existem bloqueios ao fecho">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($inspection['blockers'] as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </x-mv.alert>
            @endif

            <section class="mv-surface p-5">
                <form
                    method="POST"
                    action="{{ route('backoffice.application-review-batches.preview', $contest) }}"
                    class="space-y-5"
                >
                    @csrf
                    <input type="hidden" name="cycle" value="{{ $inspection['cycle']->value }}">
                    @foreach ($inspection['process_ids'] as $processId)
                        <input type="hidden" name="process_ids[]" value="{{ $processId }}">
                    @endforeach

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_2fr_auto] lg:items-end">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                                Ciclo
                            </p>
                            <p class="mt-2 font-semibold text-ink-900">
                                {{ $inspection['cycle']->label() }}
                            </p>
                        </div>
                        <div>
                            <label for="reason" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Fundamento do fecho
                            </label>
                            <textarea
                                id="reason"
                                name="reason"
                                rows="3"
                                required
                                maxlength="2000"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm"
                            >{{ old('reason') }}</textarea>
                        </div>
                        <button
                            type="submit"
                            class="mv-button-primary"
                            @disabled($inspection['blockers'] !== [] || $inspection['process_ids'] === [])
                        >
                            Pré-visualizar lote
                        </button>
                    </div>
                </form>
            </section>

            <section class="space-y-3">
                @foreach ($inspection['items'] as $item)
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-ink-900">
                                    {{ $item->process->process_number }}
                                </p>
                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $item->application->application_number ?: $item->application->public_id }}
                                </p>
                            </div>
                            <x-mv.badge :tone="$item->outcome->value === 'correction_required' ? 'warning' : 'success'">
                                {{ $item->outcome->label() }}
                            </x-mv.badge>
                        </div>

                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3 xl:grid-cols-6">
                            <div><dt class="text-ink-400">Obrigatórios</dt><dd class="font-semibold text-ink-800">{{ $item->readiness['total_required'] }}</dd></div>
                            <div><dt class="text-ink-400">Validados</dt><dd class="font-semibold text-ink-800">{{ $item->readiness['validated'] }}</dd></div>
                            <div><dt class="text-ink-400">Por analisar</dt><dd class="font-semibold text-ink-800">{{ $item->readiness['submitted'] }}</dd></div>
                            <div><dt class="text-ink-400">Em análise</dt><dd class="font-semibold text-ink-800">{{ $item->readiness['under_review'] }}</dd></div>
                            <div><dt class="text-ink-400">Em falta</dt><dd class="font-semibold text-ink-800">{{ $item->readiness['missing'] }}</dd></div>
                            <div><dt class="text-ink-400">Rejeitados/expirados</dt><dd class="font-semibold text-ink-800">{{ $item->readiness['rejected'] + $item->readiness['expired'] }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </section>

            @if ($batches->isNotEmpty())
                <section class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Histórico de lotes</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($batches as $batch)
                            <a
                                href="{{ route('backoffice.application-review-batches.show', $batch) }}"
                                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-ink-100 p-4 hover:border-civic-300"
                            >
                                <div>
                                    <p class="font-semibold text-ink-900">
                                        Lote {{ $batch->sequence_number }} · {{ $batch->cycle->label() }}
                                    </p>
                                    <p class="mt-1 text-sm text-ink-500">
                                        {{ $batch->item_count }} processo(s) · {{ $batch->sealed_at?->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                <x-mv.badge tone="info">{{ $batch->status->label() }}</x-mv.badge>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
