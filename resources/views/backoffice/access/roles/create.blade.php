<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Gestão de acessos"
            :title="$template ? 'Aplicar template municipal' : 'Criar perfil municipal'"
            :description="$template
                ? 'Confirme a matriz oficial e as diferenças antes de criar ou reconciliar o perfil.'
                : 'O identificador técnico será criado automaticamente e ficará estável.'"
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.roles.index') }}" class="mv-button-secondary">Voltar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <x-mv.alert tone="danger">Corrija os campos assinalados antes de guardar.</x-mv.alert>
            @endif

            @if ($template)
                <x-mv.alert tone="info">
                    O template define uma matriz exata. A aplicação não atribui utilizadores nem ativa funcionalidades municipais.
                </x-mv.alert>

                <x-mv.alert tone="warning">
                    A exportação sensível exige autorização adicional separada e não integra este template.
                </x-mv.alert>
            @endif

            <form method="POST" action="{{ route('backoffice.roles.store') }}" class="space-y-6">
                @csrf

                @if ($template)
                    <input type="hidden" name="template_key" value="{{ $template['key'] }}">

                    <x-mv.section
                        title="Identificação e proveniência"
                        description="Definição global aplicada apenas ao Município autenticado."
                        padding="p-5"
                    >
                        <dl class="grid gap-4 text-sm md:grid-cols-2">
                            <div>
                                <dt class="text-ink-500">Município</dt>
                                <dd class="mt-1 font-medium text-ink-900">{{ $templatePreview['municipality']['name'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Template</dt>
                                <dd class="mt-1 font-medium text-ink-900">{{ $template['label'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Chave</dt>
                                <dd class="mt-1 break-all font-mono text-xs text-ink-900">{{ $template['key'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Versão</dt>
                                <dd class="mt-1 font-medium text-ink-900">{{ $template['version'] }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-ink-500">Fingerprint SHA-256</dt>
                                <dd class="mt-1 break-all font-mono text-xs text-ink-900">{{ $template['fingerprint'] }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-ink-500">Descrição</dt>
                                <dd class="mt-1 text-ink-900">{{ $template['description'] }}</dd>
                            </div>
                        </dl>
                    </x-mv.section>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <x-mv.section title="Capacidades incluídas" padding="p-5">
                            <ul class="space-y-2 text-sm text-ink-700">
                                @foreach ($template['capabilities'] as $capability)
                                    <li class="flex gap-2"><span aria-hidden="true">•</span><span>{{ $capability }}</span></li>
                                @endforeach
                            </ul>
                        </x-mv.section>

                        <x-mv.section title="Permissões explicitamente excluídas" padding="p-5">
                            <ul class="space-y-2 text-sm text-ink-700">
                                @foreach ($template['excluded_permissions'] as $permission)
                                    <li><code class="break-all text-xs">{{ $permission }}</code></li>
                                @endforeach
                            </ul>
                        </x-mv.section>
                    </div>

                    <x-mv.section
                        title="Pré-visualização da aplicação"
                        description="Nenhuma alteração é feita enquanto esta página é consultada."
                        padding="p-5"
                    >
                        <div class="grid gap-5 md:grid-cols-3">
                            <div>
                                <p class="text-sm font-medium text-ink-700">A adicionar</p>
                                <p class="mt-1 text-2xl font-semibold text-ink-900">{{ count($templatePreview['permissions_to_add']) }}</p>
                                @if ($templatePreview['permissions_to_add'] !== [])
                                    <details class="mt-2 text-xs text-ink-600">
                                        <summary class="cursor-pointer font-medium">Consultar códigos</summary>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($templatePreview['permissions_to_add'] as $permission)
                                                <li><code class="break-all">{{ $permission }}</code></li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-ink-700">A manter</p>
                                <p class="mt-1 text-2xl font-semibold text-ink-900">{{ count($templatePreview['permissions_to_keep']) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-ink-700">A remover</p>
                                <p class="mt-1 text-2xl font-semibold text-ink-900">{{ count($templatePreview['permissions_to_remove']) }}</p>
                                @if ($templatePreview['permissions_to_remove'] !== [])
                                    <details class="mt-2 text-xs text-red-700">
                                        <summary class="cursor-pointer font-medium">Consultar códigos</summary>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($templatePreview['permissions_to_remove'] as $permission)
                                                <li><code class="break-all">{{ $permission }}</code></li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <x-mv.badge :tone="$templatePreview['role'] ? 'success' : 'neutral'">
                                {{ $templatePreview['role'] ? 'Perfil municipal existente' : 'Novo perfil municipal' }}
                            </x-mv.badge>
                            <x-mv.badge :tone="$templatePreview['drift'] ? 'warning' : 'success'">
                                {{ $templatePreview['drift'] ? 'Divergência detetada' : 'Sem divergência' }}
                            </x-mv.badge>
                            <x-mv.badge :tone="$templatePreview['mfa_required'] ? 'warning' : 'neutral'">
                                {{ $templatePreview['mfa_required'] ? 'MFA obrigatório por permission' : 'MFA sem impacto adicional' }}
                            </x-mv.badge>
                        </div>
                    </x-mv.section>

                    <x-mv.section title="Dependências de funcionalidades municipais" padding="p-5">
                        <ul class="space-y-2 text-sm text-ink-700">
                            @foreach ($template['entitlement_dependencies'] as $dependency)
                                <li><code>{{ $dependency }}</code></li>
                            @endforeach
                        </ul>

                        @if ($templatePreview['missing_entitlements'] !== [])
                            <x-mv.alert tone="warning" class="mt-4">
                                O perfil pode ser criado, mas as seguintes funcionalidades permanecem indisponíveis até ativação administrativa independente:
                                <ul class="mt-2 list-disc pl-5">
                                    @foreach ($templatePreview['missing_entitlements'] as $dependency)
                                        <li>{{ $dependency['label'] }} (<code>{{ $dependency['key'] }}</code>)</li>
                                    @endforeach
                                </ul>
                            </x-mv.alert>
                        @else
                            <x-mv.alert tone="success" class="mt-4">As dependências declaradas estão ativas neste Município.</x-mv.alert>
                        @endif
                    </x-mv.section>

                    @include('backoffice.access.roles.partials.permissions', [
                        'selectedPermissionIds' => $template['permission_ids'],
                        'readOnly' => true,
                    ])

                    <x-mv.section title="Confirmação explícita" padding="p-5">
                        <div class="space-y-3">
                            <x-mv.checkbox-card
                                name="confirm_template"
                                label="Confirmo a aplicação desta matriz exata ao Município indicado, sem atribuição automática de utilizadores."
                                :checked="old('confirm_template')"
                                align="start"
                                required
                            />
                            <x-input-error :messages="$errors->get('confirm_template')" />

                            @if ($templatePreview['role'] && $templatePreview['drift'])
                                <x-mv.checkbox-card
                                    name="confirm_reconcile"
                                    label="Confirmo a reconciliação explícita, incluindo a remoção das permissões divergentes acima indicadas."
                                    :checked="old('confirm_reconcile')"
                                    align="start"
                                    tone="danger"
                                    required
                                />
                                <x-input-error :messages="$errors->get('confirm_reconcile')" />
                            @endif
                        </div>
                    </x-mv.section>
                @else
                    @include('backoffice.access.roles.partials.details', ['role' => $roleDraft])
                    @include('backoffice.access.roles.partials.permissions', [
                        'selectedPermissionIds' => old('permissions', []),
                        'readOnly' => false,
                    ])
                @endif

                <x-mv.section title="Justificação administrativa" padding="p-5">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium text-ink-700">Justificação</span>
                        <textarea name="justification" rows="3" class="mv-input" required>{{ old('justification') }}</textarea>
                        <x-input-error :messages="$errors->get('justification')" />
                    </label>
                </x-mv.section>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('backoffice.roles.index') }}" class="mv-button-secondary">Cancelar</a>
                    <button type="submit" class="mv-button-primary">
                        {{ $templatePreview && $templatePreview['role'] ? 'Reconciliar perfil' : ($template ? 'Aplicar template' : 'Criar perfil') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
