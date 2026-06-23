<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Reclamações</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Pedido complementar</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.additional-information-requests.store', $complaint) }}" class="space-y-5 rounded-md border border-ink-100 bg-white p-6">@csrf
        <div><x-input-label for="subject" value="Assunto" /><x-text-input id="subject" name="subject" class="mt-1 w-full" required /></div>
        <div><x-input-label for="message" value="Mensagem" /><textarea id="message" name="message" class="mt-1 w-full rounded-md border-ink-200" required></textarea></div>
        <div><x-input-label for="instructions" value="Instruções" /><textarea id="instructions" name="instructions" class="mt-1 w-full rounded-md border-ink-200"></textarea></div>
        <div><x-input-label for="deadline_at" value="Prazo" /><x-text-input type="datetime-local" id="deadline_at" name="deadline_at" class="mt-1 w-full" required /></div>
        <div class="flex justify-end"><x-primary-button>Emitir pedido</x-primary-button></div>
    </form></div></div>
</x-app-layout>

