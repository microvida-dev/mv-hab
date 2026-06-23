<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Programas</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Novo programa</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <form method="POST" action="{{ route('admin.programs.store') }}" class="mv-surface p-6">
                @csrf
                @include('admin.programs.partials.form', ['submitLabel' => 'Criar programa'])
            </form>
        </div>
    </div>
</x-app-layout>
