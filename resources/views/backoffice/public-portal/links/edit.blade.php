<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Portal público</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar ligação institucional</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('backoffice.public-portal.links.update', $link) }}" class="grid gap-5 rounded-md border border-ink-100 bg-white p-6">
            @csrf
            @method('PUT')
            @include('backoffice.public-portal.links.form', ['link' => $link])
            <button class="mv-button-primary">Guardar ligação</button>
        </form>
    </div></div>
</x-app-layout>
