<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Sorteios</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Novo sorteio auditável</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('backoffice.lottery-draws.store') }}" class="mv-surface space-y-5 p-6">
            @csrf
            <div>
                <label class="text-sm font-semibold text-ink-700">Execução de atribuição</label>
                <select name="allocation_run_id" class="mv-input mt-1 w-full">
                    @foreach($allocationRuns as $run)
                        <option value="{{ $run->id }}">{{ $run->run_number }} — {{ $run->contest?->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="text-sm font-semibold text-ink-700">Data/hora do ato</label><input name="scheduled_at" type="datetime-local" class="mv-input mt-1 w-full"></div>
                <div><label class="text-sm font-semibold text-ink-700">Local</label><input name="location" class="mv-input mt-1 w-full"></div>
            </div>
            <div><label class="text-sm font-semibold text-ink-700">Seed opcional</label><input name="seed" class="mv-input mt-1 w-full"></div>
            <div><label class="text-sm font-semibold text-ink-700">Instruções</label><textarea name="instructions" rows="4" class="mv-input mt-1 w-full"></textarea></div>
            <button class="mv-button-primary">Criar sorteio</button>
        </form>
    </div></div>
</x-app-layout>
