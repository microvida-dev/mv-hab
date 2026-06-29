<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-ink-900">
            Nova comunicação
        </h1>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <form
            method="POST"
            action="{{ route('tenant.communications.store') }}"
            class="mv-card grid gap-5"
        >
            @csrf

            <x-ui.field label="Contrato" for="lease_contract_id" name="lease_contract_id">
                <x-ui.select id="lease_contract_id" name="lease_contract_id">
                    <option value="">Sem contrato associado</option>
                    @foreach ($contracts as $contract)
                        <option value="{{ $contract->id }}">
                            {{ $contract->contract_number }} · {{ $contract->housingUnit?->address }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field label="Assunto" for="subject" name="subject" required>
                <x-ui.input id="subject" name="subject" required maxlength="255" />
            </x-ui.field>

            <x-ui.field label="Mensagem" for="body" name="body" required>
                <x-ui.textarea id="body" name="body" rows="6" required />
            </x-ui.field>

            <div class="flex justify-end">
                <button class="mv-button-primary" type="submit">
                    Enviar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
