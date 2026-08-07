<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Configuração regulamentar</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Perfis regulamentares</h1>
                <p class="mt-1 text-sm text-ink-500">Perfis nacionais e do contexto {{ $municipality->name }}.</p>
            </div>
            @can('createBackoffice', \App\Models\AffordableRentRegulatoryProfile::class)
                <a href="{{ route('admin.regulatory-profiles.create') }}" class="mv-button-primary">
                    <x-ui-icon name="plus" class="h-4 w-4" />
                    Novo perfil
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <x-mv.alert>
                O perfil de um programa anterior a 1 de setembro de 2026 deve corresponder ao regime PAA aplicável nessa data. A partir dessa data, a plataforma resolve o regime RSAA. A publicação permanece bloqueada enquanto as fontes e regras obrigatórias estiverem incompletas.
            </x-mv.alert>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table">
                        <thead>
                            <tr>
                                <th>Perfil</th>
                                <th>Âmbito</th>
                                <th>Vigência</th>
                                <th>Estado</th>
                                <th>Completude</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($profiles as $profile)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">{{ $profile->name }}</p>
                                        <p class="mt-1 text-xs text-ink-500">{{ $profile->code }} · {{ $profile->version }} · {{ $profile->legal_regime->label() }}</p>
                                    </td>
                                    <td>{{ $profile->municipality?->name ?? 'Nacional' }}</td>
                                    <td>{{ $profile->effective_from->format('d/m/Y') }} — {{ $profile->effective_until?->format('d/m/Y') ?? 'sem termo' }}</td>
                                    <td><x-mv.badge :tone="$profile->status->value === 'active' ? 'success' : ($profile->status->value === 'archived' ? 'neutral' : 'warning')">{{ $profile->status->label() }}</x-mv.badge></td>
                                    <td><x-mv.badge :tone="$profile->configuration_status->value === 'complete' ? 'success' : 'warning'">{{ $profile->configuration_status->label() }}</x-mv.badge></td>
                                    <td class="text-right"><a href="{{ route('admin.regulatory-profiles.show', $profile) }}" class="mv-button-secondary min-h-0 px-3 py-1.5 text-xs">Abrir</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-ink-500">Ainda não existem perfis regulamentares neste contexto.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $profiles->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
