@csrf
@if (isset($notificationTemplate))
    @method('PUT')
@endif

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="code" value="Código" />
        <x-text-input id="code" name="code" class="mt-1 w-full" :value="old('code', $notificationTemplate->code ?? '')" required />
        <x-input-error class="mt-2" :messages="$errors->get('code')" />
    </div>
    <div>
        <x-input-label for="name" value="Nome" />
        <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $notificationTemplate->name ?? '')" required />
    </div>
    <div>
        <x-input-label for="template_type" value="Tipo" />
        <select id="template_type" name="template_type" class="mt-1 w-full mv-input">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('template_type', isset($notificationTemplate) ? $notificationTemplate->template_type->value : 'in_app') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="channel" value="Canal" />
        <select id="channel" name="channel" class="mt-1 w-full mv-input">
            @foreach ($channels as $value => $label)
                <option value="{{ $value }}" @selected(old('channel', isset($notificationTemplate) ? $notificationTemplate->channel->value : 'in_app') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="language" value="Idioma" />
        <x-text-input id="language" name="language" class="mt-1 w-full" :value="old('language', $notificationTemplate->language ?? 'pt-PT')" required />
    </div>
    <div>
        <x-input-label for="subject" value="Assunto" />
        <x-text-input id="subject" name="subject" class="mt-1 w-full" :value="old('subject', $notificationTemplate->subject ?? '')" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="title" value="Título" />
        <x-text-input id="title" name="title" class="mt-1 w-full" :value="old('title', $notificationTemplate->title ?? '')" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="description" value="Descrição interna" />
        <textarea id="description" name="description" rows="3" class="mt-1 w-full mv-input">{{ old('description', $notificationTemplate->description ?? '') }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="body" value="Conteúdo em texto" />
        <textarea id="body" name="body" rows="10" class="mt-1 w-full mv-input" required>{{ old('body', $notificationTemplate->body ?? '') }}</textarea>
        <p class="mt-2 text-xs text-ink-500">Variáveis: use <code>@{{ codigo_da_variavel }}</code>.</p>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="html_body" value="Conteúdo HTML opcional" />
        <textarea id="html_body" name="html_body" rows="8" class="mt-1 w-full mv-input">{{ old('html_body', $notificationTemplate->html_body ?? '') }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="sms_body" value="Conteúdo SMS opcional" />
        <textarea id="sms_body" name="sms_body" rows="3" class="mt-1 w-full mv-input">{{ old('sms_body', $notificationTemplate->sms_body ?? '') }}</textarea>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-5">
    @foreach (['requires_acknowledgement' => 'Exige tomada de conhecimento', 'is_official' => 'Comunicação oficial', 'is_default' => 'Template predefinido'] as $field => $label)
        <label class="flex items-center gap-2 text-sm text-ink-700">
            <input type="hidden" name="{{ $field }}" value="0">
            <input type="checkbox" name="{{ $field }}" value="1" class="mv-checkbox" @checked(old($field, $notificationTemplate->{$field} ?? false))>
            {{ $label }}
        </label>
    @endforeach
</div>

<div class="mt-8 flex justify-end gap-3">
    <a href="{{ route('backoffice.communications.templates.index') }}" class="mv-button-secondary">Cancelar</a>
    <x-primary-button>{{ isset($notificationTemplate) ? 'Guardar nova versão' : 'Criar template' }}</x-primary-button>
</div>
