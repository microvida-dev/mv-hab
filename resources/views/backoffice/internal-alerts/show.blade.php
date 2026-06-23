<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Alerta interno</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $internalAlert->title }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $internalAlert->alert_number }}</p>
            </div>
            <a href="{{ route('backoffice.internal-alerts.index') }}" class="mv-button-secondary">Voltar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-civic-200 bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">{{ session('success') }}</div>
            @endif
            <section class="mv-surface p-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><p class="text-sm text-ink-500">Tipo</p><p class="mt-1 font-semibold text-ink-900">{{ $internalAlert->type->label() }}</p></div>
                    <div><p class="text-sm text-ink-500">Severidade</p><p class="mt-1 font-semibold text-ink-900">{{ $internalAlert->severity->label() }}</p></div>
                    <div><p class="text-sm text-ink-500">Estado</p><p class="mt-1 font-semibold text-ink-900">{{ $internalAlert->status->label() }}</p></div>
                </div>
                <p class="mt-6 text-sm leading-6 text-ink-700">{{ $internalAlert->message }}</p>
                @if ($internalAlert->application)
                    <a class="mt-4 inline-block font-semibold text-civic-700" href="{{ route('backoffice.applications.show', $internalAlert->application) }}">Abrir candidatura associada</a>
                @endif
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Tratamento</h2>
                <form method="POST" action="{{ route('backoffice.internal-alerts.resolve', $internalAlert) }}" class="mt-4 space-y-4">
                    @csrf
                    <label class="block text-sm font-semibold text-ink-700">Notas de resolução
                        <textarea name="resolution_notes" rows="4" class="mt-1 w-full rounded-md border-ink-200"></textarea>
                    </label>
                    <div class="flex flex-wrap gap-3">
                        <button class="mv-button-primary">Resolver alerta</button>
                        <button formaction="{{ route('backoffice.internal-alerts.dismiss', $internalAlert) }}" class="mv-button-secondary">Dispensar</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
