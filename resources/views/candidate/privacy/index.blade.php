<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Privacidade</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Centro RGPD</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-6 xl:grid-cols-2">
                <form method="POST" action="{{ route('candidate.privacy.requests.store') }}" class="mv-surface grid gap-4 p-5">
                    @csrf
                    <h2 class="text-lg font-semibold text-ink-900">Novo pedido RGPD</h2>
                    <select name="request_type" class="mv-input" required>
                        @foreach (\App\Enums\DataSubjectRequestType::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <textarea name="description" class="mv-input" rows="4" placeholder="Descreva o pedido" required></textarea>
                    <button class="mv-button-primary w-fit">Submeter pedido</button>
                </form>

                <section class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Finalidades ativas</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($purposes as $purpose)
                            <form method="POST" action="{{ route('candidate.privacy.consents.store') }}" class="rounded-md border border-ink-100 p-3">
                                @csrf
                                <input type="hidden" name="consent_purpose_id" value="{{ $purpose->id }}">
                                <input type="hidden" name="text_snapshot" value="{{ $purpose->description }}">
                                <p class="font-semibold text-ink-900">{{ $purpose->name }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $purpose->legal_basis?->label() }} · {{ $purpose->is_required ? 'obrigatória' : 'opcional' }}</p>
                                @if ($purpose->requires_explicit_consent)
                                    <button class="mv-button-secondary mt-3">Dar consentimento</button>
                                @endif
                            </form>
                        @endforeach
                    </div>
                </section>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="mv-surface overflow-hidden">
                    <div class="p-5 font-semibold">Pedidos</div>
                    <div class="divide-y divide-ink-100">
                        @forelse ($requests as $requestRecord)
                            <a href="{{ route('candidate.privacy.requests.show', $requestRecord) }}" class="block p-5 hover:bg-ink-50">
                                <p class="font-semibold text-ink-900">{{ $requestRecord->request_number }}</p>
                                <p class="text-sm text-ink-500">{{ $requestRecord->request_type?->label() }} · {{ $requestRecord->status?->label() }}</p>
                            </a>
                        @empty
                            <p class="p-5 text-sm text-ink-500">Ainda não existem pedidos RGPD.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface overflow-hidden">
                    <div class="p-5 font-semibold">Consentimentos</div>
                    <div class="divide-y divide-ink-100">
                        @forelse ($consents as $consent)
                            <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                                <div>
                                    <p class="font-semibold text-ink-900">{{ $consent->purpose?->name }}</p>
                                    <p class="text-sm text-ink-500">{{ $consent->status?->label() }} · {{ $consent->consented_at?->format('d/m/Y H:i') }}</p>
                                </div>
                                @if ($consent->status === \App\Enums\ConsentStatus::Active && $consent->purpose?->requires_explicit_consent && ! $consent->purpose?->is_required)
                                    <form method="POST" action="{{ route('candidate.privacy.consents.withdraw', $consent) }}">
                                        @csrf
                                        <button class="mv-button-secondary">Retirar</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="p-5 text-sm text-ink-500">Sem consentimentos registados.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
