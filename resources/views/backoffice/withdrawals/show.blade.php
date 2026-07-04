<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Candidaturas"
            title="Desistência controlada"
            description="Valide a revisão administrativa da desistência."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Desistência">
                <x-mv.badge>{{ $withdrawal->status->label() }}</x-mv.badge>
                <p class="mt-3 text-sm text-ink-600">{{ $withdrawal->reason }}</p>
                <form method="POST" action="{{ route('backoffice.withdrawals.process', $withdrawal) }}" class="mt-5">
                    @csrf
                    <button type="submit" class="mv-button-primary">Marcar como revista</button>
                </form>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
