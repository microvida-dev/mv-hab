@if ($summary)
    <section class="mv-surface p-6">
        <div class="grid gap-4 md:grid-cols-6">
            <div>
                <p class="text-xs font-semibold uppercase text-ink-500">Total</p>
                <p class="mt-1 text-xl font-semibold text-ink-900">{{ $summary['total'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-ink-500">Coincidem</p>
                <p class="mt-1 text-xl font-semibold text-mvhab-primary">{{ $summary['matches'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-ink-500">Críticas</p>
                <p class="mt-1 text-xl font-semibold text-red-700">{{ $summary['critical'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-ink-500">Médias</p>
                <p class="mt-1 text-xl font-semibold text-orange-700">{{ $summary['medium'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-ink-500">Ligeiras</p>
                <p class="mt-1 text-xl font-semibold text-amber-700">{{ $summary['light'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-ink-500">Inconclusivas</p>
                <p class="mt-1 text-xl font-semibold text-ink-700">{{ $summary['inconclusive'] }}</p>
            </div>
        </div>
    </section>
@endif
