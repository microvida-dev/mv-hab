<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Sorteios</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Resultados</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><div class="rounded-md border border-ink-100 bg-white">
        <table class="min-w-full divide-y divide-ink-100 text-sm"><thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Posição</th><th class="px-4 py-3">Candidato</th><th class="px-4 py-3">Resultado</th><th class="px-4 py-3">Hash</th><th></th></tr></thead><tbody class="divide-y divide-ink-100">
            @foreach($results as $result)
                <tr><td class="px-4 py-3">{{ $result->draw_order }}</td><td class="px-4 py-3">{{ $result->candidate?->name }}</td><td class="px-4 py-3">{{ $result->result_type->label() }}</td><td class="px-4 py-3 font-mono text-xs">{{ $result->result_hash }}</td><td class="px-4 py-3 text-right">@if($result->selected)<form method="POST" action="{{ route('backoffice.lottery-results.winner.store', $result) }}">@csrf<button class="font-semibold text-civic-700">Registar vencedor</button></form>@endif</td></tr>
            @endforeach
        </tbody></table>
    </div></div></div>
</x-app-layout>
