<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamações"
            title="Pedido complementar"
            description="Solicite informação adicional necessária à análise da reclamação."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('backoffice.additional-information-requests.store', $complaint) }}">
                @csrf
                <x-mv.section title="Dados do pedido">
                    <div>
                        <x-input-label for="subject" value="Assunto" />
                        <x-text-input id="subject" name="subject" class="mt-1 w-full" required />
                    </div>

                    <div class="mt-5">
                        <x-input-label for="message" value="Mensagem" />
                        <textarea id="message" name="message" class="mv-input mt-1 w-full text-sm" required></textarea>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="instructions" value="Instruções" />
                        <textarea id="instructions" name="instructions" class="mv-input mt-1 w-full text-sm"></textarea>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="deadline_at" value="Prazo" />
                        <x-text-input type="datetime-local" id="deadline_at" name="deadline_at" class="mt-1 w-full" required />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="mv-button-primary">Emitir pedido</button>
                    </div>
                </x-mv.section>
            </form>
        </div>
    </div>
</x-app-layout>
