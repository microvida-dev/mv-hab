<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiência"
            title="Submeter pronúncia"
            :description="$hearing->subject"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.hearings.submit.store', $hearing) }}" class="space-y-6">
                @csrf

                <x-mv.section title="Pronúncia">
                    <div class="space-y-5">
                        <div>
                            <x-input-label for="submission_text" value="Pronúncia" />
                            <textarea id="submission_text" name="submission_text" class="mv-input mt-1 w-full" required></textarea>
                        </div>

                        <div>
                            <x-input-label for="document_submission_id" value="Documento associado" />
                            <select id="document_submission_id" name="document_submission_id" class="mv-input mt-1 w-full">
                                <option value="">Sem documento</option>
                                @foreach ($documents as $document)
                                    <option value="{{ $document->id }}">{{ $document->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-mv.section>

                <button class="mv-button-primary">Submeter pronúncia</button>
            </form>
        </div>
    </div>
</x-app-layout>
