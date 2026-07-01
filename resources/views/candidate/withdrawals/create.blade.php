<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Desistência controlada</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $application->application_number ?? 'Candidatura' }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="mv-surface p-6">
                <p class="text-sm leading-6 text-ink-700">A desistência da candidatura pode impedir a continuação deste processo. Antes de confirmar, verifique as consequências aplicáveis ao concurso e confirme que pretende desistir.</p>
                <form method="POST" action="{{ route('candidate.controlled-withdrawals.store', $application) }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                    <div>
                        <x-input-label for="reason" value="Motivo da desistência" />
                        <textarea id="reason" name="reason" rows="5" required class="mt-1 block w-full rounded-2xl border-ink-200"></textarea>
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>
                    <label class="flex gap-3 text-sm text-ink-700">
                        <input type="checkbox" name="consequence_acknowledged" value="1" class="mt-1 rounded border-ink-300">
                        <span>Confirmo que li o aviso e compreendo as consequências da desistência.</span>
                    </label>
                    <x-input-error :messages="$errors->get('consequence_acknowledged')" class="mt-2" />
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">Cancelar</a>
                        <button class="mv-button-danger">Registar pedido</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
