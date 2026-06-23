<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Templates</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Variáveis autorizadas</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
        @can('create', \App\Models\TemplateVariable::class)
            <form method="POST" action="{{ route('backoffice.communications.variables.store') }}" class="grid gap-4 rounded-md border border-ink-100 bg-white p-5 md:grid-cols-5">@csrf
                <x-text-input name="code" placeholder="codigo_variavel" required />
                <x-text-input name="name" placeholder="Nome" required />
                <select name="variable_type" class="rounded-md border-ink-200">@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                <x-text-input name="example_value" placeholder="Valor fictício" />
                <input type="hidden" name="is_active" value="1"><x-primary-button>Criar variável</x-primary-button>
            </form>
        @endcan
        <div class="overflow-hidden rounded-md border border-ink-100 bg-white">
            <table class="min-w-full divide-y divide-ink-100 text-sm"><thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Código</th><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Exemplo</th><th class="px-4 py-3">Sensível</th></tr></thead><tbody class="divide-y divide-ink-100">
                @forelse($variables as $variable)<tr><td class="px-4 py-3 font-mono text-xs">@{{ {{ $variable->code }} }}</td><td class="px-4 py-3 font-semibold">{{ $variable->name }}</td><td class="px-4 py-3">{{ $variable->variable_type->label() }}</td><td class="px-4 py-3">{{ $variable->example_value }}</td><td class="px-4 py-3">{{ $variable->is_sensitive ? 'Sim' : 'Não' }}</td></tr>@empty<tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">Sem variáveis.</td></tr>@endforelse
            </tbody></table>
        </div>
        {{ $variables->links() }}
    </div></div>
</x-app-layout>
