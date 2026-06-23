<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="contract_id" value="Contrato" />
        <select id="contract_id" name="contract_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            <option value="">Selecione</option>
            @foreach ($contracts as $contractOption)
                <option value="{{ $contractOption->id }}" @selected(old('contract_id', $payment->contract_id ?? '') == $contractOption->id)>
                    #{{ $contractOption->id }} - {{ $contractOption->citizen->name }} - {{ $contractOption->housingUnit->code }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('contract_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" value="Valor" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount', $payment->amount ?? '0.00')" required />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="reference" value="Referência" />
        <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference', $payment->reference ?? '')" required />
        <x-input-error :messages="$errors->get('reference')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="due_date" value="Data limite" />
        <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', isset($payment) ? $payment->due_date?->format('Y-m-d') : '')" required />
        <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="paid_at" value="Pago em" />
        <x-text-input id="paid_at" name="paid_at" type="datetime-local" class="mt-1 block w-full" :value="old('paid_at', isset($payment) ? $payment->paid_at?->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('paid_at')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="status" value="Estado" />
        <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', isset($payment) ? $payment->status->value : '') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>
