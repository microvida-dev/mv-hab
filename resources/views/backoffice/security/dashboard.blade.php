<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Sprint 18</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">RGPD, segurança e auditoria</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metrics as $label => $value)
                    <div class="mv-surface p-5">
                        <p class="text-sm text-ink-500">{{ str($label)->replace('_', ' ')->title() }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Revisão de storage documental</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        @foreach ($storageReview as $key => $value)
                            <div class="flex justify-between gap-4 border-b border-ink-100 pb-2">
                                <dt class="font-medium text-ink-600">{{ str($key)->replace('_', ' ')->title() }}</dt>
                                <dd class="text-right text-ink-900">{{ is_array($value) ? implode(' | ', $value) : $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Campos sensíveis</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        @foreach ($fieldReview as $key => $value)
                            <div class="flex justify-between gap-4 border-b border-ink-100 pb-2">
                                <dt class="font-medium text-ink-600">{{ str($key)->replace('_', ' ')->title() }}</dt>
                                <dd class="text-right text-ink-900">{{ is_array($value) ? implode(' | ', $value) : $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 p-5">
                        <h2 class="text-lg font-semibold text-ink-900">Alertas recentes</h2>
                    </div>
                    <div class="divide-y divide-ink-100">
                        @forelse ($alerts as $alert)
                            <div class="p-5">
                                <p class="font-semibold text-ink-900">{{ $alert->title }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $alert->detected_at?->format('d/m/Y H:i') }} · {{ $alert->severity?->label() }}</p>
                            </div>
                        @empty
                            <p class="p-5 text-sm text-ink-500">Sem alertas ativos.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 p-5">
                        <h2 class="text-lg font-semibold text-ink-900">Pedidos RGPD recentes</h2>
                    </div>
                    <div class="divide-y divide-ink-100">
                        @forelse ($rgpdRequests as $requestRecord)
                            <a href="{{ route('backoffice.security.privacy.requests.show', $requestRecord) }}" class="block p-5 hover:bg-ink-50">
                                <p class="font-semibold text-ink-900">{{ $requestRecord->request_number }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $requestRecord->request_type?->label() }} · {{ $requestRecord->status?->label() }}</p>
                            </a>
                        @empty
                            <p class="p-5 text-sm text-ink-500">Sem pedidos RGPD registados.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
