@csrf
@if(isset($notificationEventRule)) @method('PUT') @endif
<div class="grid gap-5 md:grid-cols-2">
    <div><x-input-label for="event_code" value="Código do evento" /><x-text-input id="event_code" name="event_code" class="mt-1 w-full" :value="old('event_code', $notificationEventRule->event_code ?? '')" required /></div>
    <div><x-input-label for="name" value="Nome" /><x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $notificationEventRule->name ?? '')" required /></div>
    <div><x-input-label for="recipient_type" value="Tipo de destinatário" /><select id="recipient_type" name="recipient_type" class="mt-1 w-full mv-input">@foreach(['candidate' => 'Candidato', 'tenant' => 'Arrendatário', 'municipal_technician' => 'Técnico municipal', 'jury_member' => 'Júri', 'finance_manager' => 'Gestor financeiro', 'maintenance_manager' => 'Gestor de manutenção', 'admin' => 'Administrador', 'custom_user' => 'Utilizador indicado'] as $value => $label)<option value="{{ $value }}" @selected(old('recipient_type', $notificationEventRule->recipient_type ?? 'candidate') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div><x-input-label for="notification_template_id" value="Template" /><select id="notification_template_id" name="notification_template_id" class="mt-1 w-full mv-input">@foreach($templates as $template)<option value="{{ $template->id }}" @selected((int) old('notification_template_id', $notificationEventRule->notification_template_id ?? 0) === $template->id)>{{ $template->name }}</option>@endforeach</select></div>
    <div><x-input-label for="channel" value="Canal" /><select id="channel" name="channel" class="mt-1 w-full mv-input">@foreach($channels as $value => $label)<option value="{{ $value }}" @selected(old('channel', isset($notificationEventRule) ? $notificationEventRule->channel->value : 'in_app') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div><x-input-label for="priority" value="Prioridade" /><select id="priority" name="priority" class="mt-1 w-full mv-input">@foreach($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', isset($notificationEventRule) ? $notificationEventRule->priority->value : 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div><x-input-label for="delay_minutes" value="Atraso (minutos)" /><x-text-input id="delay_minutes" name="delay_minutes" type="number" min="0" class="mt-1 w-full" :value="old('delay_minutes', $notificationEventRule->delay_minutes ?? 0)" /></div>
    <div class="md:col-span-2"><x-input-label for="description" value="Descrição" /><textarea id="description" name="description" rows="3" class="mt-1 w-full mv-input">{{ old('description', $notificationEventRule->description ?? '') }}</textarea></div>
</div>
<div class="mt-6 flex flex-wrap gap-5">
    @foreach(['requires_acknowledgement' => 'Exige tomada de conhecimento', 'send_immediately' => 'Executar imediatamente', 'is_active' => 'Regra ativa'] as $field => $label)
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" class="mv-checkbox" @checked(old($field, $notificationEventRule->{$field} ?? ($field === 'is_active')))>{{ $label }}</label>
    @endforeach
</div>
<div class="mt-8 flex justify-end gap-3"><a href="{{ route('backoffice.communications.event-rules.index') }}" class="mv-button-secondary">Cancelar</a><x-primary-button>Guardar regra</x-primary-button></div>
