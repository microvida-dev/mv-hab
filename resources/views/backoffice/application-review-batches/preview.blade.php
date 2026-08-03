<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Confirmação obrigatória</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                Pré-visualização do lote
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Nenhum dado foi alterado. Confirme o conteúdo antes do selamento.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($preview['blockers'] !== [])
                <x-mv.alert tone="danger" title="O lote não pode ser selado">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($preview['blockers'] as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </x-mv.alert>
            @endif

            <section class="grid gap-4 md:grid-cols-3">
                <x-mv.stat-card label="Ciclo" :value="$preview['cycle_label']" />
                <x-mv.stat-card label="Processos" :value="count($preview['items'])" />
                <x-mv.stat-card label="Hash do snapshot" :value="substr($preview['snapshot_hash'], 0, 12).'…'" />
            </section>

            <section class="mv-surface p-5">
                <h2 class="text-lg font-semibold text-ink-900">Fundamento</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-600">{{ $preview['reason'] }}</p>
            </section>

            <section class="space-y-3">
                @foreach ($preview['items'] as $item)
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $item->process->process_number }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $item->application->application_number ?: $item->application->public_id }}</p>
                            </div>
                            <x-mv.badge :tone="$item->outcome->value === 'correction_required' ? 'warning' : 'success'">
                                {{ $item->outcome->label() }}
                            </x-mv.badge>
                        </div>
                        <p class="mt-3 break-all font-mono text-xs text-ink-400">
                            {{ $item->snapshotHash }}
                        </p>
                    </article>
                @endforeach
            </section>

            <section class="mv-surface p-5">
                <form method="POST" action="{{ route('backoffice.application-review-batches.seal', $contest) }}">
                    @csrf
                    <input type="hidden" name="cycle" value="{{ $preview['cycle']->value }}">
                    <input type="hidden" name="reason" value="{{ $preview['reason'] }}">
                    <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                    @foreach ($preview['process_ids'] as $processId)
                        <input type="hidden" name="process_ids[]" value="{{ $processId }}">
                    @endforeach

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <a
                            href="{{ route('backoffice.application-review-batches.contest', $contest) }}"
                            class="mv-button-secondary"
                        >
                            Voltar sem alterar
                        </a>
                        <button
                            type="submit"
                            class="mv-button-primary"
                            @disabled($preview['blockers'] !== [])
                        >
                            Selar lote imutável
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
