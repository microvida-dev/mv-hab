@props([
    'summary',
])

<section class="rounded-md border border-ink-100 bg-white p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-civic-700">Case Workspace</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $summary['title'] }} {{ $summary['reference'] }}</h1>
            <p class="mt-2 max-w-3xl text-sm text-ink-500">{{ $summary['description'] }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($summary['program'])
                    <span class="rounded-md bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">{{ $summary['program'] }}</span>
                @endif
                <span class="rounded-md bg-civic-50 px-3 py-1 text-xs font-semibold text-civic-800">{{ $summary['status'] }}</span>
                <span class="rounded-md bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">Prioridade {{ $summary['priority'] }}</span>
            </div>
        </div>

        <div class="rounded-md border border-ink-100 bg-ink-50 px-4 py-3 text-sm">
            <p class="font-semibold text-ink-900">Responsável</p>
            <p class="mt-1 text-ink-600">{{ $summary['responsible'] }}</p>
            <p class="mt-2 text-xs font-semibold uppercase text-ink-500">{{ $summary['team'] }}</p>
        </div>
    </div>
</section>
