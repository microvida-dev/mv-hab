<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Modelos</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                Variáveis autorizadas
            </h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

            @can('create', \App\Models\TemplateVariable::class)
                <form
                    method="POST"
                    action="{{ route('backoffice.communications.variables.store') }}"
                    class="grid gap-5 rounded-2xl mv-surface p-6 md:grid-cols-5"
                >
                    @csrf

                    <x-ui.field label="Código" for="code">
                        <x-text-input
                            id="code"
                            name="code"
                            class="w-full"
                            placeholder="codigo_variavel"
                            required
                        />
                    </x-ui.field>

                    <x-ui.field label="Nome" for="name">
                        <x-text-input
                            id="name"
                            name="name"
                            class="w-full"
                            placeholder="Nome"
                            required
                        />
                    </x-ui.field>

                    <x-ui.field label="Tipo" for="variable_type">
                        <select
                            id="variable_type"
                            name="variable_type"
                            class="mv-input"
                        >
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Valor de exemplo" for="example_value">
                        <x-text-input
                            id="example_value"
                            name="example_value"
                            class="w-full"
                            placeholder="Valor fictício"
                        />
                    </x-ui.field>

                    <div class="flex items-end">
                        <input type="hidden" name="is_active" value="1">

                        <x-primary-button class="w-full justify-center">
                            Criar variável
                        </x-primary-button>
                    </div>
                </form>
            @endcan

            <div class="overflow-hidden rounded-2xl mv-surface">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Exemplo</th>
                            <th class="px-4 py-3">Sensível</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @forelse($variables as $variable)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">
                                    @{{ {{ $variable->code }} }}
                                </td>

                                <td class="px-4 py-3 font-semibold">
                                    {{ $variable->name }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $variable->variable_type->label() }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $variable->example_value }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $variable->is_sensitive ? 'Sim' : 'Não' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-ink-500">
                                    Sem variáveis.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $variables->links() }}

        </div>
    </div>
</x-app-layout>
