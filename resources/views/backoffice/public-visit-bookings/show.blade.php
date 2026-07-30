<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Marcação pública"
            :title="$booking->booking_reference"
            description="Dados minimizados para gestão operacional da visita."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section title="Visita">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="mv-data-label">Fogo</dt><dd class="mv-data-value">{{ $booking->housingUnit?->displayTitle() }}</dd></div>
                    <div><dt class="mv-data-label">Data</dt><dd class="mv-data-value">{{ $booking->slot?->starts_at?->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="mv-data-label">Estado</dt><dd class="mv-data-value">{{ $booking->status->label() }}</dd></div>
                    <div><dt class="mv-data-label">Participantes</dt><dd class="mv-data-value">{{ $booking->guest_count }}</dd></div>
                    <div>
                        <dt class="mv-data-label">Confirmação por email</dt>
                        <dd class="mv-data-value">
                            @if ($booking->confirmation_sent_at)
                                Enviada em {{ $booking->confirmation_sent_at->format('d/m/Y H:i') }}
                            @elseif ($booking->confirmation_failed_at)
                                Falhou em {{ $booking->confirmation_failed_at->format('d/m/Y H:i') }}
                            @else
                                Pendente
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-mv.section>

            <x-mv.section title="Contacto" description="A consulta destes dados fica registada em auditoria.">
                @if ($booking->anonymized_at)
                    <x-mv.alert tone="info" title="Dados anonimizados">
                        O prazo de retenção terminou em {{ $booking->anonymized_at->format('d/m/Y') }}.
                    </x-mv.alert>
                @else
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div><dt class="mv-data-label">Nome</dt><dd class="mv-data-value">{{ $booking->contact_name }}</dd></div>
                        <div><dt class="mv-data-label">Email</dt><dd class="mv-data-value">{{ $booking->contact_email }}</dd></div>
                        <div><dt class="mv-data-label">Telefone</dt><dd class="mv-data-value">{{ $booking->contact_phone ?: 'Não indicado' }}</dd></div>
                        <div><dt class="mv-data-label">Informação de privacidade</dt><dd class="mv-data-value">{{ $booking->privacy_notice_accepted_at?->format('d/m/Y H:i') }}</dd></div>
                    </dl>
                @endif
            </x-mv.section>

            @if ($booking->isActive())
                <x-mv.section title="Ações operacionais">
                    <div class="grid gap-5 lg:grid-cols-3">
                        @can('completeBackoffice', $booking)
                            <form method="POST" action="{{ route('backoffice.public-visit-bookings.attended', $booking) }}" class="space-y-3">
                                @csrf
                                <textarea name="notes" rows="3" class="mv-input" placeholder="Notas opcionais"></textarea>
                                <button type="submit" class="mv-button-primary">Registar comparência</button>
                            </form>
                        @endcan

                        @can('markNoShowBackoffice', $booking)
                            <form method="POST" action="{{ route('backoffice.public-visit-bookings.no-show', $booking) }}" class="space-y-3">
                                @csrf
                                <textarea name="notes" rows="3" class="mv-input" placeholder="Notas opcionais"></textarea>
                                <button type="submit" class="mv-button-secondary">Registar falta</button>
                            </form>
                        @endcan

                        @can('cancelBackoffice', $booking)
                            <form method="POST" action="{{ route('backoffice.public-visit-bookings.cancel', $booking) }}" class="space-y-3">
                                @csrf
                                <textarea name="notes" rows="3" class="mv-input" placeholder="Motivo opcional"></textarea>
                                <button type="submit" class="mv-button-secondary border-red-300 text-red-700 hover:bg-red-50">Cancelar marcação</button>
                            </form>
                        @endcan
                    </div>
                </x-mv.section>
            @endif
        </div>
    </div>
</x-app-layout>
