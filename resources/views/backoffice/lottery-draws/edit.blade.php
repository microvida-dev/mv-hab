<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Sorteios</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar sorteio</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('backoffice.lottery-draws.update', $lotteryDraw) }}" class="space-y-5 rounded-md border border-ink-100 bg-white p-6">
            @csrf @method('PATCH')
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="text-sm font-semibold text-ink-700">Data/hora</label><input name="scheduled_at" type="datetime-local" value="{{ $lotteryDraw->scheduled_at?->format('Y-m-d\\TH:i') }}" class="mt-1 w-full rounded-md border-ink-200"></div>
                <div><label class="text-sm font-semibold text-ink-700">Local</label><input name="location" value="{{ $lotteryDraw->location }}" class="mt-1 w-full rounded-md border-ink-200"></div>
            </div>
            <div><label class="text-sm font-semibold text-ink-700">Instruções</label><textarea name="instructions" rows="4" class="mt-1 w-full rounded-md border-ink-200">{{ $lotteryDraw->instructions }}</textarea></div>
            <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Guardar</button>
        </form>
    </div></div>
</x-app-layout>
