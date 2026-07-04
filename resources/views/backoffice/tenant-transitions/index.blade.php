<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Inquilino"
            title="Transições para inquilino"
            description="Execute e acompanhe transições pós-atribuição para a área do inquilino."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <form method="POST" action="{{ route('backoffice.tenant-transitions.run') }}" class="space-y-4">
                @csrf

                <x-mv.section title="Executar transição">
                    <div class="flex flex-col gap-3 md:flex-row">
                        <select name="winner_registration_id" class="mv-input flex-1">
                            @foreach ($winners as $winner)
                                <option value="{{ $winner->id }}">{{ $winner->candidate?->name }} — vencedor #{{ $winner->id }}</option>
                            @endforeach
                        </select>

                        <button class="mv-button-primary">Executar transição</button>
                    </div>
                </x-mv.section>
            </form>

            <x-mv.section padding="p-0" class="overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-4 py-3">Inquilino</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Contrato</th>
                            <th class="px-4 py-3">Conta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($transitions as $transition)
                            <tr>
                                <td class="px-4 py-3">{{ $transition->tenant?->name }}</td>
                                <td class="px-4 py-3"><x-mv.badge>{{ $transition->status->label() }}</x-mv.badge></td>
                                <td class="px-4 py-3">{{ $transition->lease_contract_id ?? 'Sem contrato ativo' }}</td>
                                <td class="px-4 py-3">{{ $transition->tenant_financial_account_id ?? 'Sem conta' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-mv.section>

            {{ $transitions->links() }}
        </div>
    </div>
</x-app-layout>
