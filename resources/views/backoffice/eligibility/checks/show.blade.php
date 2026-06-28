<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Verificação #{{ $check->id }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $check->result->label() }}</h1>
                <p class="mt-1 text-sm text-ink-500">
                    {{ $check->contest?->title ?? $check->program?->name ?? 'Sem regras aplicáveis' }}
                </p>
            </div>

            @can('rerun', $check)
                <form method="POST" action="{{ route('backoffice.eligibility.checks.rerun', $check) }}">
                    @csrf
                    <button class="mv-button-secondary">Reexecutar</button>
                </form>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Tipo</p>
                    <p class="mt-2 font-semibold">{{ $check->check_type->label() }}</p>
                </div>

                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Matriz de elegibilidade</p>
                    <p class="mt-2 font-semibold">{{ $check->ruleSet?->name ?? 'Não aplicável' }}</p>
                </div>

                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Executado por</p>
                    <p class="mt-2 font-semibold">{{ $check->executedBy?->name ?? 'Sistema' }}</p>
                </div>

                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Data</p>
                    <p class="mt-2 font-semibold">{{ $check->executed_at?->format('d/m/Y H:i') }}</p>
                </div>
            </section>

            <section class="mv-surface p-6">
                <div class="max-w-4xl">
                    <h2 class="font-semibold text-ink-900">Resumo</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ $check->summary }}</p>
                </div>

                @if ($presentation['missingData'] !== [])
                    <div class="mt-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Dados a completar</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            @foreach ($presentation['missingData'] as $item)
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <p class="font-semibold text-amber-900">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-sm leading-6 text-amber-800">{{ $item['guidance'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($check->warnings)
                    <div class="mt-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Alertas</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ink-600">
                            @foreach ($check->warnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            @if ($presentation['attentionResults'] !== [])
                <section class="mv-surface p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-ink-900">Pontos que impedem ou condicionam a elegibilidade</h2>
                            <p class="mt-1 text-sm text-ink-500">
                                Condições mínimas configuradas nas regras do programa ou concurso que exigem correção ou validação municipal.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        @foreach ($presentation['attentionResults'] as $result)
                            <article class="rounded-lg border border-ink-100 bg-white p-4 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">{{ $result['category'] }}</p>
                                        <h3 class="mt-1 font-semibold text-ink-900">{{ $result['name'] }}</h3>
                                    </div>
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-100' => $result['tone'] === 'success',
                                        'bg-red-50 text-red-700 ring-red-100' => $result['tone'] === 'danger',
                                        'bg-amber-50 text-amber-700 ring-amber-100' => $result['tone'] === 'warning',
                                        'bg-sky-50 text-sky-700 ring-sky-100' => $result['tone'] === 'info',
                                        'bg-ink-50 text-ink-600 ring-ink-100' => $result['tone'] === 'neutral',
                                    ])>
                                        {{ $result['result'] }}
                                    </span>
                                </div>

                                <dl class="mt-4 space-y-3 text-sm">
                                    <div>
                                        <dt class="font-semibold text-ink-700">Condição mínima</dt>
                                        <dd class="mt-1 leading-6 text-ink-600">{{ $result['condition'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-ink-700">Situação atual</dt>
                                        <dd class="mt-1 leading-6 text-ink-600">{{ $result['actual'] }}</dd>
                                    </div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="mv-surface overflow-hidden">
                <div class="border-b border-ink-100 px-6 py-4">
                    <h2 class="font-semibold text-ink-900">Resultados por critério</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="mv-table">
                        <thead>
                            <tr>
                                <th>Critério</th>
                                <th>Área</th>
                                <th>Resultado</th>
                                <th>Condição mínima / observação</th>
                                <th>Situação atual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($presentation['results'] as $result)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">{{ $result['name'] }}</p>
                                    </td>
                                    <td>{{ $result['category'] }}</td>
                                    <td>
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-100' => $result['tone'] === 'success',
                                            'bg-red-50 text-red-700 ring-red-100' => $result['tone'] === 'danger',
                                            'bg-amber-50 text-amber-700 ring-amber-100' => $result['tone'] === 'warning',
                                            'bg-sky-50 text-sky-700 ring-sky-100' => $result['tone'] === 'info',
                                            'bg-ink-50 text-ink-600 ring-ink-100' => $result['tone'] === 'neutral',
                                        ])>
                                            {{ $result['result'] }}
                                        </span>
                                    </td>
                                    <td class="max-w-md text-sm leading-6 text-ink-600">{{ $result['condition'] }}</td>
                                    <td class="max-w-md text-sm leading-6 text-ink-600">{{ $result['actual'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-500">Sem critérios aplicáveis.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($check->snapshots->isNotEmpty())
                <section class="mv-surface p-6">
                    <h2 class="font-semibold text-ink-900">Evidência técnica registada</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">
                        Foram guardados {{ $check->snapshots->count() }} snapshot(s) técnicos para rastreabilidade da verificação. Os dados brutos permanecem disponíveis para auditoria, sem serem misturados com a leitura operacional deste ecrã.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($check->snapshots as $snapshot)
                            <span class="rounded-full bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600 ring-1 ring-ink-100">
                                {{ str($snapshot->snapshot_type)->replace('_', ' ')->title() }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
