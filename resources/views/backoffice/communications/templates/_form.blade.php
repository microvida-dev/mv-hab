@csrf
@if (isset($notificationTemplate))
    @method('PUT')
@endif

<div class="grid gap-5 md:grid-cols-2">

    <x-ui.field for="code" name="code" label="Código" required>
        <x-ui.input
            id="code"
            name="code"
            :value="old('code', $notificationTemplate->code ?? '')"
            required
        />
        <x-input-error class="mt-2" :messages="$errors->get('code')" />
    </x-ui.field>

    <x-ui.field for="name" name="name" label="Nome" required>
        <x-ui.input
            id="name"
            name="name"
            :value="old('name', $notificationTemplate->name ?? '')"
            required
        />
    </x-ui.field>

    <x-ui.field for="template_type" name="template_type" label="Tipo">
        <x-ui.select id="template_type" name="template_type">
            @foreach ($types as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(old('template_type', isset($notificationTemplate) ? $notificationTemplate->template_type->value : 'in_app') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field for="channel" name="channel" label="Canal">
        <x-ui.select id="channel" name="channel">
            @foreach ($channels as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(old('channel', isset($notificationTemplate) ? $notificationTemplate->channel->value : 'in_app') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field for="language" name="language" label="Idioma">
        <x-ui.input
            id="language"
            name="language"
            :value="old('language', $notificationTemplate->language ?? 'pt-PT')"
            required
        />
    </x-ui.field>

    <x-ui.field for="subject" name="subject" label="Assunto">
        <x-ui.input
            id="subject"
            name="subject"
            :value="old('subject', $notificationTemplate->subject ?? '')"
        />
    </x-ui.field>

    <x-ui.field
        for="title"
        name="title"
        label="Título"
        class="md:col-span-2"
    >
        <x-ui.input
            id="title"
            name="title"
            :value="old('title', $notificationTemplate->title ?? '')"
        />
    </x-ui.field>

    <x-ui.field
        for="description"
        name="description"
        label="Descrição interna"
        class="md:col-span-2"
    >
        <x-ui.textarea
            id="description"
            name="description"
            rows="3"
        >{{ old('description', $notificationTemplate->description ?? '') }}</x-ui.textarea>
    </x-ui.field>

    <x-ui.field
        for="body"
        name="body"
        label="Conteúdo em texto"
        class="md:col-span-2"
        required
    >
        <x-ui.textarea
            id="body"
            name="body"
            rows="10"
            required
        >{{ old('body', $notificationTemplate->body ?? '') }}</x-ui.textarea>

        <p class="mt-2 text-xs text-ink-500">
            Variáveis: use <code>@{{ codigo_da_variavel }}</code>.
        </p>
    </x-ui.field>

    <x-ui.field
        for="html_body"
        name="html_body"
        label="Conteúdo HTML opcional"
        class="md:col-span-2"
    >
        <x-ui.textarea
            id="html_body"
            name="html_body"
            rows="8"
        >{{ old('html_body', $notificationTemplate->html_body ?? '') }}</x-ui.textarea>
    </x-ui.field>

    <x-ui.field
        for="sms_body"
        name="sms_body"
        label="Conteúdo SMS opcional"
        class="md:col-span-2"
    >
        <x-ui.textarea
            id="sms_body"
            name="sms_body"
            rows="3"
        >{{ old('sms_body', $notificationTemplate->sms_body ?? '') }}</x-ui.textarea>
    </x-ui.field>

</div>

<div class="mt-6 flex flex-wrap gap-5">
    @foreach ([
        'requires_acknowledgement' => 'Exige tomada de conhecimento',
        'is_official' => 'Comunicação oficial',
        'is_default' => 'Template predefinido',
    ] as $field => $label)

        <label class="flex items-center gap-2 text-sm text-ink-700">
            <input type="hidden" name="{{ $field }}" value="0">

            <x-ui.checkbox
                name="{{ $field }}"
                value="1"
                @checked(old($field, $notificationTemplate->{$field} ?? false))
            />

            <span>{{ $label }}</span>
        </label>

    @endforeach
</div>

<div class="mt-8 flex justify-end gap-3">
    <a
        href="{{ route('backoffice.communications.templates.index') }}"
        class="mv-button-secondary"
    >
        Cancelar
    </a>

    <button type="submit" class="mv-button-primary">
        {{ isset($notificationTemplate) ? 'Guardar nova versão' : 'Criar template' }}
    </button>
</div>
