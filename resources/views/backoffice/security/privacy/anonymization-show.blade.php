<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $anonymizationRequest->request_number }}</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <section class="mv-surface p-6">
            <p class="text-sm text-ink-500">{{ $anonymizationRequest->status?->label() }} · {{ $anonymizationRequest->user?->name ?? 'sem titular' }}</p>
            <p class="mt-3 text-ink-700">{{ $anonymizationRequest->reason }}</p>
            <pre class="mt-4 overflow-auto rounded-2xl bg-ink-100 p-4 text-xs">{{ json_encode($anonymizationRequest->scope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            <div class="mt-4 flex gap-3">
                <form method="POST" action="{{ route('backoffice.security.privacy.anonymization.approve', $anonymizationRequest) }}">@csrf<button class="mv-button-secondary">Aprovar</button></form>
                <form method="POST" action="{{ route('backoffice.security.privacy.anonymization.run', $anonymizationRequest) }}">@csrf<button class="mv-button-primary">Executar</button></form>
            </div>
        </section>
    </div></div>
</x-app-layout>
