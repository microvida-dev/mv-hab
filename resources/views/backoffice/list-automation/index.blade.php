<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Listas</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Automação assistida de listas</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $contest->title }}</p>
            </div>
            <a href="{{ route('backoffice.lists.provisional.index') }}" class="mv-button-secondary">Listas existentes</a>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))<div class="rounded-md border border-civic-200 bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">{{ session('success') }}</div>@endif
            <section class="mv-surface p-6">
                <p class="text-sm leading-6 text-ink-600">A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('backoffice.lists.automation.provisional', $contest) }}">@csrf <button class="mv-button-primary">Gerar lista provisória</button></form>
                    <form method="POST" action="{{ route('backoffice.lists.automation.definitive', $contest) }}">@csrf <button class="mv-button-secondary">Gerar lista definitiva</button></form>
                </div>
            </section>
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Execuções</h2>
                <div class="mt-4 overflow-x-auto"><table class="mv-table"><thead><tr><th>Número</th><th>Tipo</th><th>Estado</th><th>Incluídos</th><th>Avisos</th><th></th></tr></thead><tbody>@forelse ($runs as $run)<tr><td class="font-mono text-xs">{{ $run->run_number }}</td><td>{{ $run->type->label() }}</td><td>{{ $run->status->label() }}</td><td>{{ $run->included_count }}</td><td>{{ $run->warnings_count }}</td><td class="text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.lists.automation-runs.show', $run) }}">Abrir</a></td></tr>@empty<tr><td colspan="6">Sem execuções.</td></tr>@endforelse</tbody></table></div>
                <div class="mt-4">{{ $runs->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
