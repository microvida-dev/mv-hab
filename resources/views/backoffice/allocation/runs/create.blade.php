<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Atribuição</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Nova execução de atribuição</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8"><x-flash-message /><form method="POST" action="{{ route('backoffice.allocation.runs.store') }}" class="space-y-4 rounded-md border border-ink-100 bg-white p-6">@csrf
        <label class="block text-sm font-medium text-ink-700">Lista definitiva<select name="definitive_list_id" class="mt-1 w-full rounded-md border-ink-200">@foreach($definitiveLists as $list)<option value="{{ $list->id }}">{{ $list->code }} · {{ $list->contest?->title }}</option>@endforeach</select></label>
        <label class="block text-sm font-medium text-ink-700">Regra de atribuição<select name="allocation_rule_set_id" class="mt-1 w-full rounded-md border-ink-200"><option value="">Resolver automaticamente</option>@foreach($ruleSets as $ruleSet)<option value="{{ $ruleSet->id }}">{{ $ruleSet->name }}</option>@endforeach</select></label>
        <label class="block text-sm font-medium text-ink-700">Método<select name="allocation_method" class="mt-1 w-full rounded-md border-ink-200"><option value="">Usar regra ativa</option>@foreach($methods as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label class="block text-sm font-medium text-ink-700">Semente do sorteio<input name="seed" class="mt-1 w-full rounded-md border-ink-200"></label>
        <label class="block text-sm font-medium text-ink-700">Notas<textarea name="notes" class="mt-1 w-full rounded-md border-ink-200"></textarea></label>
        <div class="flex justify-end"><button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Executar</button></div>
    </form></div></div>
</x-app-layout>
