<section class="mv-surface p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-ink-900">Score IA</h2>
            <p class="mt-1 text-sm text-ink-500">{{ $score?->summary ?? 'Score ainda não calculado para esta análise.' }}</p>
        </div>
        @if ($score)
            <div class="text-left sm:text-right">
                <p class="text-4xl font-semibold text-ink-900">{{ $score->score }}%</p>
                <p class="mt-1 text-sm font-semibold text-civic-700">{{ $score->label->label() }}</p>
            </div>
        @endif
    </div>

    @if ($score)
        <div class="mt-6 grid gap-3 md:grid-cols-5">
            @foreach (($score->components ?? []) as $component => $value)
                <div class="rounded-md border border-ink-100 p-3">
                    <p class="text-xs font-semibold uppercase text-ink-500">{{ str_replace('_', ' ', $component) }}</p>
                    <p class="mt-1 text-xl font-semibold text-ink-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div>
                <h3 class="text-sm font-semibold text-ink-800">Sinais positivos</h3>
                <ul class="mt-2 space-y-1 text-sm text-civic-700">
                    @forelse (($score->explanation['positives'] ?? []) as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>Sem sinais positivos suficientes.</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-ink-800">Atenção</h3>
                <ul class="mt-2 space-y-1 text-sm text-amber-700">
                    @forelse (($score->explanation['attention'] ?? []) as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>Sem indicadores relevantes.</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-ink-800">Recomendação</h3>
                <ul class="mt-2 space-y-1 text-sm text-ink-700">
                    @foreach (($score->explanation['recommendations'] ?? ['Validar no fluxo técnico normal.']) as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</section>
