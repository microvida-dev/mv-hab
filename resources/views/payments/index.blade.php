<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">
                Pagamentos
            </h2>

            <a href="{{ route('payments.create') }}" class="mv-button-primary">
                Novo pagamento
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-ui.table :headers="['Referência', 'Contrato', 'Valor', 'Vencimento', 'Estado', 'Ações']">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="font-semibold text-ink-900">
                            {{ $payment->reference }}
                        </td>

                        <td>
                            #{{ $payment->contract->id }} - {{ $payment->contract->citizen->name }}
                        </td>

                        <td>
                            {{ number_format((float) $payment->amount, 2, ',', '.') }} €
                        </td>

                        <td>
                            {{ $payment->due_date?->format('d/m/Y') }}
                        </td>

                        <td>
                            {{ $payment->status->label() }}
                        </td>

                        <x-ui.table-actions>
                            <a href="{{ route('payments.show', $payment) }}" class="font-semibold text-mvhab-primary">
                                Ver
                            </a>

                            <a href="{{ route('payments.edit', $payment) }}" class="font-semibold text-mvhab-primary">
                                Editar
                            </a>

                            <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Eliminar este pagamento?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="font-semibold text-danger-700">
                                    Eliminar
                                </button>
                            </form>
                        </x-ui.table-actions>
                    </tr>
                @empty
                    <x-ui.table-empty :colspan="6" message="Ainda não existem pagamentos registados." />
                @endforelse
            </x-ui.table>

            {{ $payments->links() }}
        </div>
    </div>
</x-app-layout>
