<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Cobrança {{ $tenantChargeRun->run_number }}</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">As cobranças automáticas registadas nesta plataforma correspondem à geração operacional de valores a cobrar e não implicam, por si só, movimento bancário externo sem integração devidamente configurada.</p></div>
        @foreach ($tenantChargeRun->items as $item)
            <div class="mv-card">
                <p class="font-semibold">{{ $item->tenant?->name }} · {{ number_format((float) $item->amount, 2, ',', '.') }} EUR</p>
                <p class="text-sm text-ink-500">{{ $item->status }} · {{ $item->message }}</p>
            </div>
        @endforeach
    </div>
</x-app-layout>
