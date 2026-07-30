<x-public-layout
    title="Visita cancelada"
    description="A marcação pública de visita foi cancelada."
>
    <section class="mv-section">
        <div class="mv-container max-w-3xl">
            <div class="mv-card p-8" role="status" aria-live="polite">
                <x-mv.badge tone="success">Cancelamento concluído</x-mv.badge>
                <h1 class="mv-heading mt-5">A marcação foi cancelada</h1>
                <p class="mv-description mt-4">
                    A capacidade do horário foi libertada. Não é necessária qualquer ação adicional.
                </p>
                <a href="{{ route('public.housing-offer.index') }}" class="mv-button-primary mt-6">
                    Consultar oferta habitacional
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
