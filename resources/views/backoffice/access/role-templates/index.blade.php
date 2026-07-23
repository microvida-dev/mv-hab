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

            <div class="grid gap-5 lg:grid-cols-3">
                @foreach ($templates as $template)
                    <x-mv.section :title="$template['label']" :description="$template['description']" padding="p-5">
                        <div class="flex items-center justify-between gap-3">
                            <x-mv.badge>{{ count($template['permissions']) }} permissões recomendadas</x-mv.badge>
                            @can('create', App\Models\Role::class)
                                <a href="{{ route('backoffice.role-templates.create', $template['key']) }}" class="mv-button-primary">
                                    Rever matriz
                                </a>
                            @endcan
                        </div>
                    </x-mv.section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
