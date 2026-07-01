<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Portal público</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Nova ligação institucional</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('backoffice.public-portal.links.store') }}" class="mv-surface grid gap-5 p-6">
            @csrf
            @include('backoffice.public-portal.links.form', ['link' => $link])
            <button class="mv-button-primary">Criar ligação</button>
        </form>
    </div></div>
</x-app-layout>
