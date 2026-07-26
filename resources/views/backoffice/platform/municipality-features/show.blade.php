<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Funcionalidades municipais"
            :title="$municipality->name"
            description="Ative ou desative funcionalidades com justificação auditável."
        >
            <x-slot name="actions">
                @can('audit', [App\Models\MunicipalityFeatureEntitlement::class, $municipality])
                    <a href="{{ route('backoffice.platform.municipality-features.audit', $municipality) }}" class="mv-button-secondary">Consultar auditoria</a>
                @endcan
                <a href="{{ route('backoffice.platform.municipality-features.index') }}" class="mv-button-secondary">Voltar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @error('feature')
                <x-mv.alert tone="danger">{{ $message }}</x-mv.alert>
            @enderror

            <x-mv.alert>
                As permissões e o isolamento municipal continuam a ser validados separadamente. Não são alterados perfis nem utilizadores nesta página.
            </x-mv.alert>

            @foreach ($featureStates as $state)
                @php($feature = $state['feature'])
                <x-mv.section :title="$feature->label()" :description="$feature->value">
                    <div class="grid gap-5 lg:grid-cols-[1fr_22rem] lg:items-start">
                        <div class="space-y-3 text-sm text-ink-600">
                            <p>
                                Estado:
                                <x-mv.badge :tone="$state['enabled'] ? 'success' : 'neutral'">
                                    {{ $state['enabled'] ? 'Ativa' : 'Inativa' }}
                                </x-mv.badge>
                            </p>
                            <p>
                                <span class="font-medium text-ink-800">Dependências:</span>
                                @if ($state['dependencies'] === [])
                                    Nenhuma.
                                @else
                                    {{ collect($state['dependencies'])->map(fn ($dependency) => $dependency->label())->join(', ') }}.
                                @endif
                            </p>
                            @if ($state['blocked_reason'])
                                <x-mv.alert tone="warning">{{ $state['blocked_reason'] }}</x-mv.alert>
                            @endif
                        </div>

                        @can('update', [App\Models\MunicipalityFeatureEntitlement::class, $municipality])
                            @if ($state['enabled'])
                                <form method="POST" action="{{ route('backoffice.platform.municipality-features.disable', [$municipality, $feature]) }}" class="space-y-3">
                                    @csrf
                                    <label class="grid gap-1 text-sm">
                                        <span class="font-medium text-ink-700">Justificação para desativar</span>
                                        <textarea name="justification" rows="3" required minlength="10" maxlength="1000" class="mv-input"></textarea>
                                    </label>
                                    <x-input-error :messages="$errors->get('justification')" />
                                    <button type="submit" class="mv-button-secondary" @disabled(! $state['can_disable'])>
                                        Desativar
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('backoffice.platform.municipality-features.enable', [$municipality, $feature]) }}" class="space-y-3">
                                    @csrf
                                    <label class="grid gap-1 text-sm">
                                        <span class="font-medium text-ink-700">Justificação para ativar</span>
                                        <textarea name="justification" rows="3" required minlength="10" maxlength="1000" class="mv-input"></textarea>
                                    </label>
                                    <x-input-error :messages="$errors->get('justification')" />
                                    <button type="submit" class="mv-button-primary" @disabled(! $state['can_enable'])>
                                        Ativar
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </x-mv.section>
            @endforeach
        </div>
    </div>
</x-app-layout>
