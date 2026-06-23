<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Renovação {{ $registrationRenewal->renewal_number }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $registrationRenewal->status->label() }}</h1>
        </div>
    </x-slot>

    @php($snapshot = $registrationRenewal->updated_snapshot ?? [])
    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <form method="POST" action="{{ route('candidate.registration-renewals.update', $registrationRenewal) }}" class="mv-surface space-y-6 p-6">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ([
                        'phone' => 'Telefone',
                        'mobile_phone' => 'Telemóvel',
                        'document_type' => 'Tipo de documento',
                        'document_valid_until' => 'Validade do documento',
                        'address' => 'Morada',
                        'postal_code' => 'Código postal',
                        'city' => 'Localidade',
                        'parish' => 'Freguesia',
                        'municipality' => 'Município',
                        'nationality' => 'Nacionalidade',
                    ] as $field => $label)
                        <label class="block">
                            <span class="text-sm font-semibold text-ink-800">{{ $label }}</span>
                            <input name="{{ $field }}" value="{{ old($field, $snapshot[$field] ?? '') }}" class="mt-1 w-full rounded-md border-ink-200">
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end"><button class="mv-button-secondary">Guardar alterações</button></div>
            </form>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Submissão</h2>
                @if (($registrationRenewal->missing_fields ?? []) !== [])
                    <p class="mt-2 text-sm text-amber-700">Campos em falta: {{ implode(', ', $registrationRenewal->missing_fields) }}</p>
                @else
                    <p class="mt-2 text-sm text-ink-600">Os dados mínimos estão preenchidos.</p>
                @endif
                <form method="POST" action="{{ route('candidate.registration-renewals.submit', $registrationRenewal) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="confirm_data_current" value="1">
                    <button class="mv-button-primary">Submeter renovação</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
