<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Configuração regulamentar</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Novo perfil regulamentar</h1>
            <p class="mt-1 text-sm text-ink-500">Contexto operacional: {{ $municipality->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <form method="POST" action="{{ route('admin.regulatory-profiles.store') }}" class="mv-surface p-6">
                @csrf
                @include('admin.regulatory-profiles.partials.form', ['submitLabel' => 'Criar perfil'])
            </form>
        </div>
    </div>
</x-app-layout>
