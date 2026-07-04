<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Pedido complementar"
            :title="$additionalInformationRequest->request_number"
            description="Acompanhamento do pedido de informação adicional associado à reclamação."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Pedido">
                <div class="flex flex-wrap gap-2">
                    <x-mv.badge>{{ $additionalInformationRequest->status->label() }}</x-mv.badge>
                    <x-mv.badge>Prazo {{ $additionalInformationRequest->deadline_at->format('d/m/Y H:i') }}</x-mv.badge>
                </div>

                <h2 class="mt-4 font-semibold">{{ $additionalInformationRequest->subject }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm">{{ $additionalInformationRequest->message }}</p>
            </x-mv.section>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('backoffice.additional-information-requests.close', $additionalInformationRequest) }}">
                    @csrf
                    <button type="submit" class="mv-button-secondary">Fechar</button>
                </form>
                <form method="POST" action="{{ route('backoffice.additional-information-requests.mark-overdue', $additionalInformationRequest) }}">
                    @csrf
                    <button type="submit" class="mv-button-secondary">Marcar vencido</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
