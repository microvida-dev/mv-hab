<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiência"
            :title="$hearing->subject"
            description="Foi-lhe concedida audiência de interessados para se pronunciar sobre os elementos indicados."
        >
            <x-slot name="actions">
                <a class="mv-button-primary" href="{{ route('candidate.hearings.submit', $hearing) }}">Pronunciar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.alert>
                A sua pronúncia deve ser submetida dentro do prazo definido.
            </x-mv.alert>

            <x-mv.section title="Detalhe da audiência">
                <div class="flex flex-wrap items-center gap-3">
                    <x-mv.badge>{{ $hearing->status->label() }}</x-mv.badge>
                    <span class="text-sm text-ink-500">Prazo {{ $hearing->deadline_at->format('d/m/Y H:i') }}</span>
                </div>

                <p class="mt-5 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $hearing->message }}</p>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
