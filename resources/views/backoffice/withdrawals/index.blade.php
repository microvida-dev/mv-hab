<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Desistências controladas</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface overflow-hidden"><table class="min-w-full divide-y divide-ink-100 text-sm"><tbody class="divide-y divide-ink-100">@forelse($withdrawals as $withdrawal)<tr><td class="px-5 py-4 font-semibold">{{ $withdrawal->application?->application_number }}</td><td class="px-5 py-4">{{ $withdrawal->status->label() }}</td><td class="px-5 py-4 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.withdrawals.show', $withdrawal) }}">Ver</a></td></tr>@empty<tr><td class="px-5 py-8 text-center text-ink-500">Sem desistências.</td></tr>@endforelse</tbody></table></section>
        {{ $withdrawals->links() }}
    </div></div>
</x-app-layout>
