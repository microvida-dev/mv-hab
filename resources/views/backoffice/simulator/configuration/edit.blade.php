<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Configuração</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Simulador avançado</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <form method="POST" action="{{ route('backoffice.simulator.configuration.update') }}" class="mv-surface space-y-6 p-6">
                @csrf
                @method('PATCH')
                <label class="block"><span class="text-sm font-semibold text-ink-800">Nome</span><input name="name" value="{{ old('name', $configuration->name) }}" class="mt-1 w-full rounded-md border-ink-200" required></label>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="block"><span class="text-sm font-semibold text-ink-800">Concursos recomendados</span><input type="number" min="1" max="20" name="max_recommended_contests" value="{{ old('max_recommended_contests', $configuration->max_recommended_contests) }}" class="mt-1 w-full rounded-md border-ink-200" required></label>
                    <label class="block"><span class="text-sm font-semibold text-ink-800">Taxa de esforço padrão</span><input type="number" min="1" max="100" step="0.01" name="default_effort_rate" value="{{ old('default_effort_rate', $configuration->default_effort_rate) }}" class="mt-1 w-full rounded-md border-ink-200" required></label>
                    <label class="block"><span class="text-sm font-semibold text-ink-800">Retenção de sessões</span><input type="number" min="1" max="365" name="session_retention_days" value="{{ old('session_retention_days', $configuration->session_retention_days) }}" class="mt-1 w-full rounded-md border-ink-200" required></label>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach ([
                        'is_active' => 'Ativo',
                        'anonymous_simulator_enabled' => 'Simulador público',
                        'candidate_simulator_enabled' => 'Simulador candidato',
                    ] as $field => $label)
                        <label class="flex items-center gap-2 text-sm text-ink-700">
                            <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-ink-300 text-civic-700" @checked(old($field, $configuration->{$field}))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end"><button class="mv-button-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
