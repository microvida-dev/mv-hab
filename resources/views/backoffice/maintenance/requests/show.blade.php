<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $maintenanceRequest->request_number ?? '#'.$maintenanceRequest->id }}</h1></x-slot>
    <div class="space-y-6">
        <div class="mv-card grid gap-3 md:grid-cols-3"><div><p class="text-xs text-ink-500">Estado</p><p class="font-semibold">{{ $maintenanceRequest->status?->label() }}</p></div><div><p class="text-xs text-ink-500">Habitação</p><p class="font-semibold">{{ $maintenanceRequest->housingUnit?->code }}</p></div><div><p class="text-xs text-ink-500">Urgência</p><p class="font-semibold">{{ $maintenanceRequest->urgency?->label() }}</p></div></div>
        <div class="mv-card space-y-2"><h2 class="font-semibold">{{ $maintenanceRequest->title }}</h2><p>{{ $maintenanceRequest->description }}</p><p class="text-sm text-ink-500">{{ $maintenanceRequest->location_in_property }}</p></div>
        <div class="mv-card grid gap-3 md:grid-cols-2">
            <form method="POST" action="{{ route('backoffice.maintenance.requests.review', $maintenanceRequest) }}" class="grid gap-2">@csrf<input type="hidden" name="urgency" value="{{ $maintenanceRequest->urgency?->value ?? 'normal' }}"><textarea class="mv-input" name="review_notes" placeholder="Notas de análise"></textarea><button class="mv-button-secondary">Colocar em análise</button></form>
            <form method="POST" action="{{ route('backoffice.maintenance.requests.resolve', $maintenanceRequest) }}" class="grid gap-2">@csrf<textarea class="mv-input" name="resolution_summary" placeholder="Resumo da resolução" required></textarea><button class="mv-button-secondary">Resolver</button></form>
            <form method="POST" action="{{ route('backoffice.maintenance.requests.reject', $maintenanceRequest) }}" class="grid gap-2">@csrf<textarea class="mv-input" name="rejection_reason" placeholder="Motivo da rejeição" required></textarea><button class="mv-button-secondary">Rejeitar</button></form>
            <form method="POST" action="{{ route('backoffice.maintenance.requests.close', $maintenanceRequest) }}" class="grid gap-2">@csrf<textarea class="mv-input" name="closure_notes" placeholder="Notas de fecho"></textarea><button class="mv-button-primary">Fechar</button></form>
        </div>
        <form method="POST" action="{{ route('backoffice.maintenance.attachments.store', $maintenanceRequest) }}" enctype="multipart/form-data" class="mv-card grid gap-3">@csrf<input class="mv-input" type="file" name="attachment" required><label class="text-sm"><input type="checkbox" name="visible_to_tenant" value="1"> Visível ao arrendatário</label><button class="mv-button-secondary">Carregar anexo</button></form>
        <div class="mv-card"><h2 class="font-semibold">Histórico</h2>@foreach ($maintenanceRequest->statusHistories as $history)<p class="mt-2 text-sm">{{ $history->changed_at?->format('d/m/Y H:i') }} · {{ $history->to_status?->label() }} · {{ $history->reason }}</p>@endforeach</div>
    </div>
</x-app-layout>
