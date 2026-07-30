<x-public-layout
    title="Cancelar visita"
    description="Cancelamento seguro de uma marcação pública de visita."
>
    <section class="mv-section">
        <div class="mv-container max-w-3xl">
            <div class="mv-card p-8">
                <x-mv.badge tone="warning">Cancelamento</x-mv.badge>
                <h1 class="mv-heading mt-5">Cancelar a visita</h1>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Fogo</dt>
                        <dd class="mv-data-value">{{ $booking->housingUnit?->displayTitle() }}</dd>
                    </div>
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Data e hora</dt>
                        <dd class="mv-data-value">{{ $booking->slot?->starts_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Referência</dt>
                        <dd class="mv-data-value">{{ $booking->booking_reference }}</dd>
                    </div>
                    <div class="mv-card-muted p-4">
                        <dt class="mv-data-label">Estado</dt>
                        <dd class="mv-data-value">{{ $booking->status->label() }}</dd>
                    </div>
                </dl>

                @if ($booking->status === \App\Enums\PublicVisitBookingStatus::Cancelled)
                    <x-mv.alert tone="success" title="Marcação já cancelada" class="mt-6">
                        Não é necessária qualquer ação adicional.
                    </x-mv.alert>
                @else
                    <form method="POST" action="{{ route('public.visit-bookings.destroy', ['token' => $token]) }}" class="mt-6">
                        @csrf
                        <button type="submit" class="mv-button-secondary border-red-300 text-red-700 hover:bg-red-50">
                            Confirmar cancelamento
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</x-public-layout>
