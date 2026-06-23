<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Renovação</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Iniciar renovação de registo</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.registration-renewals.store') }}" class="mv-surface space-y-5 p-6">
                @csrf
                <label class="block">
                    <span class="text-sm font-semibold text-ink-800">Motivo</span>
                    <input name="reason" value="{{ old('reason', 'candidate_update') }}" class="mt-1 w-full rounded-md border-ink-200">
                </label>
                <div class="flex justify-end"><button class="mv-button-primary">Iniciar</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
