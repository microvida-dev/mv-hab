<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Gestão de acessos"
            title="Modelos de perfis municipais"
            description="Pontos de partida de menor privilégio. Cada matriz deve ser revista antes da criação do perfil."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.roles.index') }}" class="mv-button-secondary">Voltar aos perfis</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @error('template')
                <x-mv.alert tone="danger">{{ $message }}</x-mv.alert>
            @enderror

            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($templates as $template)
                    <x-mv.section :title="$template['label']" :description="$template['description']" padding="p-5">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-mv.badge>{{ count($template['permissions']) }} permissões exatas</x-mv.badge>
                                <x-mv.badge>Versão {{ $template['version'] }}</x-mv.badge>
                                @if (in_array('reports.export_sensitive', $template['excluded_permissions'], true))
                                    <x-mv.badge tone="warning">Sem exportação sensível</x-mv.badge>
                                @endif
                            </div>

                            <ul class="space-y-1 text-sm text-ink-700">
                                @foreach ($template['capabilities'] as $capability)
                                    <li class="flex gap-2"><span aria-hidden="true">•</span><span>{{ $capability }}</span></li>
                                @endforeach
                            </ul>

                            @if ($template['entitlement_dependencies'] !== [])
                                <p class="text-xs text-ink-500">
                                    Funcionalidades necessárias:
                                    <span class="font-mono">{{ implode(', ', $template['entitlement_dependencies']) }}</span>
                                </p>
                            @endif

                            <p class="break-all font-mono text-xs text-ink-500">
                                Fingerprint: {{ $template['fingerprint'] }}
                            </p>

                            @can('create', App\Models\Role::class)
                                <div class="flex justify-end">
                                    <a href="{{ route('backoffice.role-templates.create', $template['key']) }}" class="mv-button-primary">
                                        Rever aplicação
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </x-mv.section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
