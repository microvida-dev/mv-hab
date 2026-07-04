<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiência prévia"
            title="Pronúncia de audiência"
            description="Registe a decisão municipal sobre a pronúncia submetida pelo candidato."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Pronúncia">
                <p class="text-sm text-ink-600">{{ $submission->submission_text }}</p>

                <form method="POST" action="{{ route('backoffice.preliminary-hearings.decide', $submission) }}" class="mt-6 space-y-4">
                    @csrf
                    <select name="accepted" class="mv-input block w-full text-sm">
                        <option value="1">Aceitar</option>
                        <option value="0">Rejeitar</option>
                    </select>
                    <textarea name="review_notes" rows="4" class="mv-input block w-full text-sm"></textarea>
                    <button type="submit" class="mv-button-primary">Registar decisão</button>
                </form>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
