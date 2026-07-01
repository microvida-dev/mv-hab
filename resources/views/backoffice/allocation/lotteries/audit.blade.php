<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Atribuição</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Auditoria do sorteio</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8"><x-flash-message /><div class="rounded-2xl border border-ink-100 bg-white p-6 text-sm"><p class="font-semibold">Hash auditável</p><p class="mt-2 break-all text-ink-600">{{ $lotteryRun->audit_hash }}</p><pre class="mt-4 overflow-auto rounded-2xl bg-ink-900 p-4 text-xs text-white">{{ json_encode($lotteryRun->audit_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></div></div>
</x-app-layout>
