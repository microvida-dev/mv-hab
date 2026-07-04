<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Pedido de manutenção"
            :title="$maintenanceRequest->request_number ?? '#'.$maintenanceRequest->id"
            :description="$maintenanceRequest->title"
        />
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-3 md:grid-cols-3">
            <x-mv.stat-card label="Estado" :value="$maintenanceRequest->status?->label() ?? '-'" />
            <x-mv.stat-card label="Habitação" :value="$maintenanceRequest->housingUnit?->code ?? '-'" />
            <x-mv.stat-card label="Urgência" :value="$maintenanceRequest->urgency?->label() ?? '-'" />
        </div>

        <x-mv.section title="Descrição">
            <p class="text-sm leading-6 text-ink-700">{{ $maintenanceRequest->description }}</p>
            <p class="mt-2 text-sm text-ink-500">{{ $maintenanceRequest->location_in_property }}</p>
        </x-mv.section>

        <x-mv.section title="Ações operacionais">
            <div class="grid gap-3 md:grid-cols-2">
                <form method="POST" action="{{ route('backoffice.maintenance.requests.review', $maintenanceRequest) }}" class="grid gap-2">
                    @csrf
                    <input type="hidden" name="urgency" value="{{ $maintenanceRequest->urgency?->value ?? 'normal' }}">
                    <textarea class="mv-input" name="review_notes" placeholder="Notas de análise"></textarea>
                    <button class="mv-button-secondary">Colocar em análise</button>
                </form>
                <form method="POST" action="{{ route('backoffice.maintenance.requests.resolve', $maintenanceRequest) }}" class="grid gap-2">
                    @csrf
                    <textarea class="mv-input" name="resolution_summary" placeholder="Resumo da resolução" required></textarea>
                    <button class="mv-button-secondary">Resolver</button>
                </form>
                <form method="POST" action="{{ route('backoffice.maintenance.requests.reject', $maintenanceRequest) }}" class="grid gap-2">
                    @csrf
                    <textarea class="mv-input" name="rejection_reason" placeholder="Motivo da rejeição" required></textarea>
                    <button class="mv-button-secondary">Rejeitar</button>
                </form>
                <form method="POST" action="{{ route('backoffice.maintenance.requests.close', $maintenanceRequest) }}" class="grid gap-2">
                    @csrf
                    <textarea class="mv-input" name="closure_notes" placeholder="Notas de fecho"></textarea>
                    <button class="mv-button-primary">Fechar</button>
                </form>
            </div>
        </x-mv.section>

        <form method="POST" action="{{ route('backoffice.maintenance.attachments.store', $maintenanceRequest) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <x-mv.section title="Anexo">
                <x-mv.file-input id="maintenance_attachment" name="attachment" required />

                <label class="mt-4 flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="visible_to_tenant" value="1" class="mv-checkbox">
                    Visível ao arrendatário
                </label>
            </x-mv.section>

            <button class="mv-button-secondary">Carregar anexo</button>
        </form>

        <x-mv.section title="Histórico">
            @foreach ($maintenanceRequest->statusHistories as $history)
                <p class="mt-2 text-sm">{{ $history->changed_at?->format('d/m/Y H:i') }} · {{ $history->to_status?->label() }} · {{ $history->reason }}</p>
            @endforeach
        </x-mv.section>
    </div>
</x-app-layout>
