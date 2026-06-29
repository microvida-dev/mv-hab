@props([
    'steps' => [],
])

<section class="mv-card p-5">
    <x-ui.section-header title="Progresso visual" />
    <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($steps as $step)
            <div class="rounded-2xl border border-ink-100 px-3 py-2">
                <p class="text-sm font-semibold text-ink-900">{{ $step['label'] }}</p>
                <x-ui.status-badge :status="$step['status']" class="mt-2" />
            </div>
        @endforeach
    </div>
</section>
