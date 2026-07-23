<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Administração da plataforma"
            title="Funcionalidades municipais"
            description="Consulte a disponibilidade operacional definida para cada Município."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.alert>
                Uma funcionalidade ativa não concede permissões. O acesso continua dependente do perfil do utilizador e da Policy do registo.
            </x-mv.alert>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <thead>
                            <tr>
                                <th>Município</th>
                                @foreach ($features as $feature)
                                    <th>{{ $feature->label() }}</th>
                                @endforeach
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($municipalities as $municipality)
                                @php
                                    $activeFeatures = $municipality->featureEntitlements
                                        ->where('enabled', true)
                                        ->map(fn ($entitlement) => $entitlement->feature_key->value)
                                        ->all();
                                @endphp
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">{{ $municipality->name }}</p>
                                        <p class="text-xs text-ink-500">{{ $municipality->code }}</p>
                                    </td>
                                    @foreach ($features as $feature)
                                        <td>
                                            <x-mv.badge :tone="in_array($feature->value, $activeFeatures, true) ? 'success' : 'neutral'">
                                                {{ in_array($feature->value, $activeFeatures, true) ? 'Ativa' : 'Inativa' }}
                                            </x-mv.badge>
                                        </td>
                                    @endforeach
                                    <td class="text-right">
                                        <a href="{{ route('backoffice.platform.municipality-features.show', $municipality) }}" class="mv-button-secondary">
                                            Configurar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-ink-500">Não existem Municípios configurados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $municipalities->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
