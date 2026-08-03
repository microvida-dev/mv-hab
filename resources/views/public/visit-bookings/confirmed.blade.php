<x-public-layout
    title="Visita marcada"
    description="Confirmação da marcação pública de visita."
>
    <section class="mv-section">
        <div class="mv-container max-w-3xl">
            <div class="mv-card p-8" role="status" aria-live="polite">
                <x-mv.badge tone="success">Marcação confirmada</x-mv.badge>
                <h1 class="mv-heading mt-5">A visita ficou registada</h1>
                <p class="mv-description mt-4">
                    Guarde a referência e o endereço de cancelamento. Também será enviada uma confirmação para o email indicado.
                </p>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Referência</dt>
                        <dd class="mv-data-value">{{ $confirmation['reference'] }}</dd>
                    </div>
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Fogo</dt>
                        <dd class="mv-data-value">{{ $confirmation['housing_title'] }}</dd>
                    </div>
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Data e hora</dt>
                        <dd class="mv-data-value">
                            {{ \Illuminate\Support\Carbon::parse($confirmation['starts_at'])->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Participantes</dt>
                        <dd class="mv-data-value">{{ $confirmation['guest_count'] }}</dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-2xl border border-signal-200 bg-signal-50 p-5">
                    <p class="font-semibold text-ink-900">Cancelamento seguro</p>
                    <p class="mt-2 text-sm text-ink-600">
                        Este endereço é pessoal. Não o partilhe.
                    </p>
                    <a href="{{ $confirmation['cancellation_url'] }}" class="mv-button-secondary mt-4">
                        Abrir página de cancelamento
                    </a>
                </div>

                <a href="{{ route('public.housing-offer.index') }}" class="mv-button-primary mt-6">
                    Voltar à oferta habitacional
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
