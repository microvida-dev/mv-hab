<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Editar equipa municipal</h1></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @error('access')
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>
            @enderror
            <form method="POST" action="{{ route('backoffice.teams.update', $team) }}" class="mv-surface grid gap-4 p-6">
                @csrf
                @method('PATCH')
                @include('backoffice.access.teams.partials.form', ['team' => $team])
                <button class="mv-button-primary">Guardar equipa</button>
            </form>
        </div>
    </div>
</x-app-layout>
