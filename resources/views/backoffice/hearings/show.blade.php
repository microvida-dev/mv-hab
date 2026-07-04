<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiência"
            :title="$hearing->hearing_number"
            description="Detalhe da audiência e pronúncias recebidas do candidato."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Resumo da audiência">
                <div class="flex flex-wrap gap-2">
                    <x-mv.badge>{{ $hearing->status->label() }}</x-mv.badge>
                    <x-mv.badge>{{ $hearing->hearing_type->label() }}</x-mv.badge>
                    <x-mv.badge>Prazo {{ $hearing->deadline_at->format('d/m/Y H:i') }}</x-mv.badge>
                </div>

                <h2 class="mt-4 font-semibold">{{ $hearing->subject }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm">{{ $hearing->message }}</p>
            </x-mv.section>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('backoffice.hearings.issue', $hearing) }}">
                    @csrf
                    <button type="submit" class="mv-button-primary">Emitir</button>
                </form>
                <form method="POST" action="{{ route('backoffice.hearings.close', $hearing) }}">
                    @csrf
                    <button type="submit" class="mv-button-secondary">Fechar</button>
                </form>
            </div>

            @foreach ($hearing->submissions as $submission)
                <x-mv.section title="Pronúncia recebida">
                    <p class="text-sm">{{ $submission->submission_text }}</p>
                    <form method="POST" action="{{ route('backoffice.hearing-submissions.accept', $submission) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="mv-button-secondary">Aceitar pronúncia</button>
                    </form>
                </x-mv.section>
            @endforeach
        </div>
    </div>
</x-app-layout>
