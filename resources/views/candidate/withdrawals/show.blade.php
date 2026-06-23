<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Confirmação de desistência</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $withdrawal->application->application_number ?? 'Candidatura' }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface p-6">
                <p class="text-sm font-semibold text-ink-900">Estado: {{ $withdrawal->status->label() }}</p>
                <p class="mt-3 text-sm leading-6 text-ink-600">{{ $withdrawal->reason }}</p>
                @if ($withdrawal->status->value === 'pending_confirmation')
                    <form method="POST" action="{{ route('candidate.withdrawals.confirm', $withdrawal) }}" class="mt-6 space-y-4">
                        @csrf
                        <label class="flex gap-3 text-sm text-ink-700">
                            <input type="checkbox" name="confirm_withdrawal" value="1" class="mt-1 rounded border-ink-300">
                            <span>Confirmo definitivamente a desistência da candidatura.</span>
                        </label>
                        <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white">Confirmar desistência</button>
                    </form>
                    <form method="POST" action="{{ route('candidate.withdrawals.cancel', $withdrawal) }}" class="mt-3">
                        @csrf
                        <button class="rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700">Cancelar pedido</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
