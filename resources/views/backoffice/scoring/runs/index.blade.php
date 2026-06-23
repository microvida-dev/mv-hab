<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="text-sm font-semibold text-civic-700">Classificação</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Execuções</h1></div>
            @can('create', \App\Models\ScoringRun::class)
                <a href="{{ route('backoffice.scoring.runs.create') }}" class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white hover:bg-civic-800">Executar classificação</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><div class="overflow-hidden rounded-md border border-ink-100 bg-white">
        <table class="min-w-full divide-y divide-ink-100 text-sm">
            <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Matriz</th><th class="px-4 py-3">Contexto</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Pontuadas</th><th class="px-4 py-3">Snapshots</th><th class="px-4 py-3"></th></tr></thead>
            <tbody class="divide-y divide-ink-100">@forelse ($runs as $run)<tr><td class="px-4 py-3 font-semibold">{{ $run->id }}</td><td class="px-4 py-3">{{ $run->ruleSet?->name }}</td><td class="px-4 py-3 text-ink-600">{{ $run->contest?->title ?? $run->program?->name ?? 'Sem contexto' }}</td><td class="px-4 py-3 text-ink-600">{{ $run->status->label() }}</td><td class="px-4 py-3 text-ink-600">{{ $run->application_scores_count }}</td><td class="px-4 py-3 text-ink-600">{{ $run->ranking_snapshots_count }}</td><td class="px-4 py-3 text-right"><a href="{{ route('backoffice.scoring.runs.show', $run) }}" class="font-semibold text-civic-700">Abrir</a></td></tr>@empty<tr><td colspan="7" class="px-4 py-8 text-center text-ink-500">Sem execuções.</td></tr>@endforelse</tbody>
        </table>
    </div><div class="mt-4">{{ $runs->links() }}</div></div></div>
</x-app-layout>
