<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Execução de lista</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $listAutomationRun->run_number }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $listAutomationRun->contest->title }} · {{ $listAutomationRun->status->label() }}</p>
            </div>
            <form method="POST" action="{{ route('backoffice.lists.automation-runs.approve', $listAutomationRun) }}">@csrf <input type="hidden" name="confirm_reviewed" value="1"><button class="mv-button-primary">Aprovar após revisão</button></form>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">@if (session('success'))<div class="rounded-md border border-civic-200 bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">{{ session('success') }}</div>@endif<section class="grid gap-4 sm:grid-cols-4"><div class="mv-surface p-4"><p class="text-sm text-ink-500">Total</p><p class="text-2xl font-semibold">{{ $listAutomationRun->total_candidates }}</p></div><div class="mv-surface p-4"><p class="text-sm text-ink-500">Incluídos</p><p class="text-2xl font-semibold">{{ $listAutomationRun->included_count }}</p></div><div class="mv-surface p-4"><p class="text-sm text-ink-500">Excluídos</p><p class="text-2xl font-semibold">{{ $listAutomationRun->excluded_count }}</p></div><div class="mv-surface p-4"><p class="text-sm text-ink-500">Avisos</p><p class="text-2xl font-semibold">{{ $listAutomationRun->warnings_count }}</p></div></section><section class="mv-surface p-6"><h2 class="text-lg font-semibold text-ink-900">Resultado</h2><pre class="mt-4 overflow-auto rounded-md bg-ink-50 p-4 text-xs">{{ json_encode($listAutomationRun->result_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></section><section class="mv-surface p-6"><h2 class="text-lg font-semibold text-ink-900">Critérios preservados</h2><pre class="mt-4 overflow-auto rounded-md bg-ink-50 p-4 text-xs">{{ json_encode($listAutomationRun->criteria_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></section></div></div>
</x-app-layout>
