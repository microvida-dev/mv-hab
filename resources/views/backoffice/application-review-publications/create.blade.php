<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Publicação coletiva</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $batch->contest->title }}</h1>
            <p class="mt-1 text-sm text-ink-500">Lote {{ $batch->sequence_number }} · {{ $batch->cycle->label() }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <x-mv.alert tone="warning" title="Operação coletiva e irreversível">
                Todos os {{ $batch->item_count }} resultados ficarão visíveis após o mesmo commit e com o mesmo instante oficial de publicação.
            </x-mv.alert>
            <section class="grid gap-4 sm:grid-cols-3">
                <x-mv.stat-card label="Estado do lote" :value="$batch->status->label()" />
                <x-mv.stat-card label="Resultados" :value="$batch->item_count" />
                <x-mv.stat-card label="Selado em" :value="$batch->sealed_at->format('d/m/Y H:i')" />
            </section>
            <form method="POST" action="{{ route('backoffice.application-review-publications.preview', $batch) }}" class="mv-surface space-y-5 p-6">
                @csrf
                <div>
                    <label for="reason" class="block text-sm font-semibold text-ink-800">Fundamento da publicação</label>
                    <textarea id="reason" name="reason" rows="5" required maxlength="2000" class="mt-2 w-full rounded-md border-ink-200">{{ old('reason', 'Publicação coletiva dos resultados da revisão documental do lote selado.') }}</textarea>
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>
                <div class="flex flex-wrap gap-3">
                    <button class="mv-button-primary">Pré-visualizar publicação</button>
                    <a href="{{ route('backoffice.application-review-batches.show', $batch) }}" class="mv-button-secondary">Voltar ao lote</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
