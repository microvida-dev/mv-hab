<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Documento adicional"
            :title="$submission->title"
            description="Registe a decisão técnica sobre a submissão adicional."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Análise documental">
                <p class="text-sm text-ink-600">{{ $submission->description }}</p>

                <form method="POST" action="{{ route('backoffice.additional-document-submissions.decide', $submission) }}" class="mt-6 space-y-4">
                    @csrf
                    <select name="accepted" class="mv-input block w-full text-sm">
                        <option value="1">Aceitar</option>
                        <option value="0">Rejeitar</option>
                    </select>
                    <textarea name="rejection_reason" rows="4" class="mv-input block w-full text-sm" placeholder="Motivo de rejeição, se aplicável"></textarea>
                    <button type="submit" class="mv-button-primary">Registar decisão</button>
                </form>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
