<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiência prévia"
            title="Submeter audiência prévia"
            :description="$hearing->subject"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-mv.section
                title="Pronúncia"
                :description="'Prazo '.$hearing->deadline_at->format('d/m/Y H:i')"
            >
                <form method="POST" action="{{ route('candidate.hearings.submit.store', $hearing) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $hearing->application_id }}">
                    <input type="hidden" name="subject" value="{{ $hearing->subject }}">

                    <textarea name="body" rows="7" required class="mv-input block w-full"></textarea>

                    <button class="mv-button-primary">Submeter pronúncia</button>
                </form>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
