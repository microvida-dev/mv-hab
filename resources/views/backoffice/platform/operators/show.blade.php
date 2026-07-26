<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Operador de plataforma"
            :title="$assignment->user?->name ?? 'Conta removida'"
            description="Detalhe da associação estrutural e respetiva evidência."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.platform.operators.index') }}" class="mv-button-secondary">Voltar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section title="Associação">
                <dl class="grid gap-5 text-sm md:grid-cols-2">
                    <div>
                        <dt class="text-ink-500">ID do utilizador</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $assignment->user_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Estado</dt>
                        <dd class="mt-1">
                            <x-mv.badge :tone="$assignment->isActive() ? 'success' : 'neutral'">
                                {{ $assignment->isActive() ? 'Ativo' : 'Revogado' }}
                            </x-mv.badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Origem</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $assignment->grant_source->value }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Concedido em</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $assignment->granted_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Concedido por</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $assignment->grantedBy?->name ?? 'Bootstrap aprovado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Revogado em</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $assignment->revoked_at?->format('d/m/Y H:i') ?? 'Não aplicável' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-ink-500">Justificação da concessão</dt>
                        <dd class="mt-1 whitespace-pre-line font-medium text-ink-900">{{ $assignment->grant_justification }}</dd>
                    </div>
                    @if ($assignment->approval_reference_primary || $assignment->approval_reference_secondary)
                        <div>
                            <dt class="text-ink-500">Aprovação primária</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $assignment->approval_reference_primary }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">Aprovação secundária</dt>
                            <dd class="mt-1 font-medium text-ink-900">{{ $assignment->approval_reference_secondary }}</dd>
                        </div>
                    @endif
                    @if ($assignment->revoke_justification)
                        <div class="md:col-span-2">
                            <dt class="text-ink-500">Justificação da revogação</dt>
                            <dd class="mt-1 whitespace-pre-line font-medium text-ink-900">{{ $assignment->revoke_justification }}</dd>
                        </div>
                    @endif
                </dl>
            </x-mv.section>

            @if ($assignment->isActive())
                @can('revoke', $assignment)
                    <x-mv.section
                        title="Revogar scope global"
                        description="A revogação é auditada e não remove perfis nem permissões."
                    >
                        <form method="POST" action="{{ route('backoffice.platform.operators.revoke', $assignment) }}" class="space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="justification" value="Justificação da revogação" />
                                <textarea id="justification" name="justification" rows="4" required minlength="10" maxlength="1000" class="mt-1 block w-full rounded-md border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary">{{ old('justification') }}</textarea>
                                <x-input-error :messages="$errors->get('justification')" class="mt-2" />
                                <x-input-error :messages="$errors->get('platform_operator')" class="mt-2" />
                            </div>
                            <x-danger-button type="submit">Revogar scope</x-danger-button>
                        </form>
                    </x-mv.section>
                @endcan
            @endif
        </div>
    </div>
</x-app-layout>
