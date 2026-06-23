<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Análise administrativa</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $process->process_number }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('backoffice.application-reviews.store', $process) }}" class="mv-surface space-y-5 p-6">
                @csrf
                <div>
                    <label class="text-sm font-semibold text-ink-700">Tipo de análise</label>
                    <select name="review_type" class="mt-1 w-full rounded-md border-ink-300 text-sm">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-ink-700">Resumo</label>
                    <textarea name="summary" rows="3" class="mt-1 w-full rounded-md border-ink-300 text-sm"></textarea>
                </div>
                <div class="rounded-md border border-ink-100 p-4">
                    <h2 class="text-sm font-semibold text-ink-900">Item de análise</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <input name="items[0][name]" class="rounded-md border-ink-300 text-sm" placeholder="Nome do ponto analisado">
                        <select name="items[0][result]" class="rounded-md border-ink-300 text-sm">
                            @foreach ($results as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <textarea name="items[0][message]" rows="2" class="mt-3 w-full rounded-md border-ink-300 text-sm" placeholder="Mensagem"></textarea>
                    <label class="mt-3 flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="items[0][requires_correction]" value="1" class="rounded border-ink-300">Requer aperfeiçoamento</label>
                </div>
                <button class="mv-button-primary">Criar análise</button>
            </form>
        </div>
    </div>
</x-app-layout>
