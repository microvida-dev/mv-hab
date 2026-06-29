@csrf

@if (isset($notificationEventRule))
    @method('PUT')
@endif

<div class="grid gap-5 md:grid-cols-2">
    <x-ui.field for="event_code" name="event_code" label="Código do evento" required>
        <x-ui.input
            id="event_code"
            name="event_code"
            :value="old('event_code', $notificationEventRule->event_code ?? '')"
            required
        />
    </x-ui.field>

    <x-ui.field for="name" name="name" label="Nome" required>
        <x-ui.input
            id="name"
            name="name"
            :value="old('name', $notificationEventRule->name ?? '')"
            required
        />
    </x-ui.field>

    <x-ui.field for="recipient_type" name="recipient_type" label="Tipo de destinatário">
        <x-ui.select id="recipient_type" name="recipient_type">
            @foreach ([
                'candidate' => 'Candidato',
                'tenant' => 'Arrendatário',
                'municipal_technician' => 'Técnico municipal',
                'jury_member' => 'Júri',
                'finance_manager' => 'Gestor financeiro',
                'maintenance_manager' => 'Gestor de manutenção',
                'admin' => 'Administrador',
                'custom_user' => 'Utilizador indicado',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(old('recipient_type', $notificationEventRule->recipient_type ?? 'candidate') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field for="notification_template_id" name="notification_template_id" label="Template">
        <x-ui.select id="notification_template_id" name="notification_template_id">
            @foreach ($templates as $template)
                <option
                    value="{{ $template->id }}"
                    @selected((int) old('notification_template_id', $notificationEventRule->notification_template_id ?? 0) === $template->id)
                >
                    {{ $template->name }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field for="channel" name="channel" label="Canal">
        <x-ui.select id="channel" name="channel">
            @foreach ($channels as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(old('channel', isset($notificationEventRule) ? $notificationEventRule->channel->value : 'in_app') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field for="priority" name="priority" label="Prioridade">
        <x-ui.select id="priority" name="priority">
            @foreach ($priorities as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(old('priority', isset($notificationEventRule) ? $notificationEventRule->priority->value : 'normal') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field for="delay_minutes" name="delay_minutes" label="Atraso (minutos)">
        <x-ui.input
            id="delay_minutes"
            name="delay_minutes"
            type="number"
            min="0"
            :value="old('delay_minutes', $notificationEventRule->delay_minutes ?? 0)"
        />
    </x-ui.field>

    <x-ui.field
        for="description"
        name="description"
        label="Descrição"
        class="md:col-span-2"
    >
        <x-ui.textarea
            id="description"
            name="description"
            rows="3"
        >{{ old('description', $notificationEventRule->description ?? '') }}</x-ui.textarea>
    </x-ui.field>
</div>

<div class="mt-6 flex flex-wrap gap-5">
    @foreach ([
        'requires_acknowledgement' => 'Exige tomada de conhecimento',
        'send_immediately' => 'Executar imediatamente',
        'is_active' => 'Regra ativa',
    ] as $field => $label)
        <label class="flex items-center gap-2 text-sm text-ink-700">
            <input type="hidden" name="{{ $field }}" value="0">

            <x-ui.checkbox
                name="{{ $field }}"
                value="1"
                @checked(old($field, $notificationEventRule->{$field} ?? ($field === 'is_active')))
            />

            <span>{{ $label }}</span>
        </label>
    @endforeach
</div>

<div class="mt-8 flex justify-end gap-3">
    <a
        href="{{ route('backoffice.communications.event-rules.index') }}"
        class="mv-button-secondary"
    >
        Cancelar
    </a>

    <button type="submit" class="mv-button-primary">
        Guardar regra
    </button>
</div>
