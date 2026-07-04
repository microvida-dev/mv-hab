<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Comunicações"
            title="Nova comunicação"
            description="Envie uma mensagem aos serviços municipais sobre o seu contrato ou habitação."
        />
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <form
            method="POST"
            action="{{ route('tenant.communications.store') }}"
            class="space-y-6"
        >
            @csrf

            <x-mv.section title="Mensagem">
                <div class="grid gap-5">
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
                </div>
            </x-mv.section>

            <div class="flex justify-end">
                <button class="mv-button-primary" type="submit">
                    Enviar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
