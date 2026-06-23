<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Área do Candidato</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">As minhas candidaturas</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section class="mv-surface p-8 text-center">
                <x-ui-icon name="file" class="mx-auto h-8 w-8 text-civic-700" />
                <h2 class="mt-4 font-semibold text-ink-900">Ainda não disponível</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-ink-500">A submissão e acompanhamento de candidaturas será disponibilizada numa fase seguinte da plataforma.</p>
                <a href="{{ route('public.contests.index') }}" class="mv-button-secondary mt-5">Consultar concursos públicos</a>
            </section>
        </div>
    </div>
</x-app-layout>
