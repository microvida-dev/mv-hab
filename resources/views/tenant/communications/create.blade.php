<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Nova comunicação</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <form class="mv-card grid gap-4" method="POST" action="{{ route('tenant.communications.store') }}">
            @csrf
            <label class="grid gap-1 text-sm font-medium">Contrato
                <select class="mv-input" name="lease_contract_id">
                    <option value="">Sem contrato associado</option>
                    @foreach ($contracts as $contract)
                        <option value="{{ $contract->id }}">{{ $contract->contract_number }} · {{ $contract->housingUnit?->address }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-medium">Assunto <input class="mv-input" name="subject" required maxlength="255"></label>
            <label class="grid gap-1 text-sm font-medium">Mensagem <textarea class="mv-input" name="body" rows="6" required></textarea></label>
            <button class="mv-button-primary" type="submit">Enviar</button>
        </form>
    </div>
</x-app-layout>
